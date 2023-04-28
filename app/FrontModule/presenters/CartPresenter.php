<?php


namespace App\FrontModule\Presenters;


use App\FrontModule\Components\Cart\ICartAddressFormFactory;
use App\FrontModule\Components\Cart\ICartConfirmFactory;
use App\FrontModule\Components\Cart\ICartItemsFormFactory;
use App\FrontModule\Components\Cart\ICartTotalFactory;
use App\FrontModule\Components\User\ISignInFormFactory;
use App\Model\Country\CountryRepository;

use App\Model\Order\OrderRepository;
use App\Model\LocaleRepository;
use App\Model\Order\Order;
use App\Model\Payment\Payment24payService;
use App\Model\User\UserRepository;
use Nette\Http\IRequest;
use PaySys\CardPay\Security\Response;
use PaySys\PaySys\Exception;
use Tracy\Debugger;


class CartPresenter extends BasePresenter
{
    /**
     * @var ISignInFormFactory
     * @inject
     */
    public $signInForm;

    /**
     * @var ICartItemsFormFactory
     * @inject
     */
    public $cartItemsForm;

    /**
     * @var ICartTotalFactory
     * @inject
     */
    public $cartTotal;

    /**
     * @var ICartAddressFormFactory
     * @inject
     */
    public $cartAddressForm;

    /**
     * @var ICartConfirmFactory
     * @inject
     */
    public $cartConfirm;

    /**
     * @var OrderRepository
     * @inject
     */
    public $orderRepository;

    /**
     * @var LocaleRepository
     * @inject
     */
    public $localeRepository;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var Payment24payService
     * @inject
     */
    public $payment24payService;

    /** @var IRequest */
    private $httpRequest;

    /** @var Response */
    private $bankResponse;

    protected $isSummary = false;

    public function startup()
    {
        parent::startup();
        if (!$this->allowCart) {
            $this->redirect('Homepage:');
        }
    }

    public function actionDefault()
    {
//        $this->cartRepository->recalculateCartWhenSignIn();
        $this->template->items = $this->cartRepository->getItems();
        $post = $this->getRequest()->getPost();
        if (isset($post['country'])) {
            $this['cartAddress']->setCountry($post['country']);
        }
        if (isset($post['shipping'])) {
            $this['cartAddress']->setShipping($post['shipping']);
        }
        $this->template->currency = $this->localeRepository->getCurrencyByLang($this->getParameter('locale'));
    }

    public function renderDefault()
    {
        $locale = strtoupper($this->localeRepository->getCurrentLocale());
        $this->template->parnerItemsAmount = $this->cartRepository->getPartnerProductsCount();
        $this->template->parnerGratisItemsAmount = $this->cartRepository->getGratisPartnerProductsCount();
        $this->template->freeDeliveryRemains = $this->settingRepository->getFreeDelivery($locale) - $this->cartRepository->getTotalPrice();
        $this->template->reachedLevel = $this->cartRepository->getReachedLevel();
        $this->template->reachedLevelId = $this->cartRepository->getReachedLevelId();
    }

    public function actionSummary()
    {
        $this->isSummary = true;
        if (!$this->cartRepository->isActiveOrder() || !$this->orderRepository->isActiveOrder()) {
            $this->redirect('default');
        } else {
            $this->template->orderNumber = $this->orderRepository->makeOrderNumber();
            $this->template->items = $this->cartRepository->getItems();
            $orderData = $this->orderRepository->getDataFromSession();
            $this->template->orderData = $orderData;
            $this->template->currency = $this->localeRepository->getCurrencyByLang($this->getParameter('locale'));
        }
    }

    public function actionFinish()
    {
        $orderData = $this->orderRepository->getDataFromSession();
        $order = null;
        try {
            $order = new Order($this->orderRepository->getById($orderData['orderId'])->fetch());
        } catch (\Throwable $e) {}

        $this->template->order = $order;

        if (!$this->cartRepository->isActiveOrder() || !$this->orderRepository->isActiveOrder()) {
            $this->redirect('default');
        }
        $this->cartRepository->clearCart();
    }

    public function actionFinishCard()
    {
        $orderData = $this->orderRepository->getDataFromSession();
        $newOrder = $this->orderRepository->getById($orderData['orderId'])->fetch();
        $this->template->payForm = $this->payment24payService->getForm($newOrder);
        $order = null;
        try {
            $order = new Order($this->orderRepository->getById($orderData['orderId'])->fetch());
        } catch (\Throwable $e) {}

        $this->template->order = $order;

        if (!$this->cartRepository->isActiveOrder() || !$this->orderRepository->isActiveOrder()) {
            $this->redirect('default');
        }

    }

    public function actionCardSuccess()
    {
        Debugger::log($this->getParameters(), 'p24pay-callback');
        if (!$this->cartRepository->isActiveOrder() || !$this->orderRepository->isActiveOrder()) {
            Debugger::log('No order', 'p24pay-callback');
            $this->redirect('default');
        }
        $result = $this->getParameter('Result');
        if($result == 'OK') {
            $number = $this->getParameter('MsTxnId');
            $this->orderRepository->sendCardOrder($number);
            $this->cartRepository->clearCart();
            $this->redirect('finish');
        }
        $this->redirect('trustpayError');
    }

    public function actionCardNotify()
    {
        $request = $this->getHttpRequest()->getRawBody();
        $post = $this->getHttpRequest()->getPost();
        Debugger::log('Post: '. serialize($post), 'p24pc');
        Debugger::log('RawBody: '. $request, 'p24pc');
        $this->terminate();
    }

    public function actionTrustpayCancel()
    {
        if (!$this->cartRepository->isActiveOrder() || !$this->orderRepository->isActiveOrder()) {
            $this->redirect('default');
        }
        $this->cartRepository->clearCart();

    }

    public function actionTrustpayError()
    {
        if (!$this->cartRepository->isActiveOrder() || !$this->orderRepository->isActiveOrder()) {
            $this->redirect('default');
        }
        $this->cartRepository->clearCart();
    }

    public function actionTrustpayNotify()
    {
        $this->orderRepository->setPaid($this->getParameter('REF'));
        die;
    }

    /**
     * @throws \Nette\Application\AbortException
     */
    public function actionProcessCardPay()
    {
        if (!$this->cartRepository->isActiveOrder() || !$this->orderRepository->isActiveOrder()) {
            $this->redirect('default');
        }
        try {
            $paid = $this->bankResponse->paid($this->httpRequest->getQuery());
            if($paid == false) {
                $this->redirect('trustpayError');
            }
            $params = $this->httpRequest->getQuery();
            $this->orderRepository->setPaid($params['VS']);
            $this->redirect('finish');
        } catch (Exception $exception) {
            $this->redirect('trustpayError');
        }
        $this->redirect('trustpayError');
    }

    public function handleGetPayments($shippingId)
    {
        $this['cartAddress']->setShipping($shippingId);
        $this['cartAddress']->redrawControl('addressForm');
        $this['cartAddress']->redrawControl('paymentOptions');
    }

    public function handleGetShippings($countryId)
    {
        if (strlen($countryId) < 1) {
            $countryId = null;
            $this['cartAddress']->setShipping(null);
            $this['cartAddress']->redrawControl('paymentOptions');
        }
        $this['cartAddress']->setCountry($countryId);
        $this['cartAddress']->redrawControl('addressForm');
        $this['cartAddress']->redrawControl('shippingOptions');
    }

    /* ---------------------------------------- Components ---------------------------------------------------------- */

    public function createComponentSignInForm()
    {
        $form = $this->signInForm->create();
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        $form->onError[] = function () {
            $this->flashMessage('Zadali ste zle e-mail alebo heslo', 'danger');
            $this->redirect('this');
        };
        return $form;
    }

    public function createComponentCartItemsForm()
    {
        $form = $this->cartItemsForm->create();
        if($this->isSummary) {
            $form->setSummary();
        }
        $that = $this;
        $form->onDone[] = function () use ($that){
            if ($this->cartRepository->getProductsCount() < 1) {
                $this->redirect('this');
            }
            if ($this->isAjax()) {
                $this['cartItemsForm']->setAjax();
                $this->redrawControl('cartItems');
                $that['cartAddress']->redrawControl();
            } else {
                $this->redirect('this');
            }
        };
        return $form;
    }

    public function createComponentCartTotal()
    {
        $form = $this->cartTotal->create();
        if($this->isSummary) {
            $form->setSummary();
        }
        $form->onDone[] = function () {
            if ($this->isAjax()) {
                $this->redrawControl('cartItems');
            } else {
                $this->redirect('this');
            }
        };
        return $form;
    }

    public function createComponentCartAddress()
    {
        $form = $this->cartAddressForm->create();
        $form->setLocale($this->getParameter('locale'));
        $form->onDone[] = function () {
            $this->redirect('summary');
        };
        return $form;
    }

    public function createComponentCartConfirm()
    {
        $form = $this->cartConfirm->create();
        $form->onDone[] = function ($addAccount) {
            if($addAccount) {
                $this->userRepository->createUserFromOrder();
            }
            $isCardPayment = $this->orderRepository->makeOrder();
            if($isCardPayment == true) {
                $this->redirect('finishCard');
            }
            $this->redirect('finish');
        };
        return $form;
    }
}