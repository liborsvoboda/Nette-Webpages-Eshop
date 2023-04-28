<?php


namespace App\FrontModule\Presenters;


use App\Components\Macros\PriceFilter;
use App\FrontModule\Components\Customer\ICustomerGridFactory;
use App\FrontModule\Components\Order\INewOrderFormFactory;
use App\FrontModule\Components\User\ICreateUserFormFactory;
use App\FrontModule\Components\User\IEditFormFactory;
use App\FrontModule\Components\User\ILostPasswordFormFactory;
use App\FrontModule\Components\User\INewPasswordFormFactory;
use App\Model\BaseRepository;
use App\Model\Commission\MonthlyCommissionRepository;
use App\Model\Order\OrderRepository;
use App\Model\Product\ProductRepository;
use App\Model\Shipping\ShippingRepository;
use App\Model\User\UserRepository;
use App\Model\MarketingGallery\MarketingGalleryRepository;
use App\Model\Gallery\GalleryRepository;
use App\Model\Order\Order;
use Nette\Application\BadRequestException;
use Nette\Application\LinkGenerator;
use Nette\Utils\DateTime;
use App\Model\UserLevel\UserLevelRepository;

class AccountPresenter extends BasePresenter
{
    /**
     * @var OrderRepository
     * @inject
     */
    public $orderRepository;

    /**
     * @var ProductRepository
     * @inject
     */
    public $productRepository;

    /**
     * @var IEditFormFactory
     * @inject
     */
    public $editForm;

    /**
     * @var ILostPasswordFormFactory
     * @inject
     */
    public $lostPasswordForm;

    /**
     * @var INewPasswordFormFactory
     * @inject
     */
    public $newPasswordForm;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var MarketingGalleryRepository
     * @inject
     */
    public $marketingGalleryRepository;

    /**
     * @var GalleryRepository
     * @inject
     */
    public $galleryRepository;

    /**
     * @var ICustomerGridFactory
     * @inject
     */
    public $customerGrid;

    /**
     * @var LinkGenerator
     * @inject
     */
    public $linkGenerator;

    /**
     * @var MonthlyCommissionRepository
     * @inject
     */
    public $monthlyCommissionRepository;

    /**
     * @var ICreateUserFormFactory
     * @inject
     */
    public $createUserForm;

    /**
     * @var INewOrderFormFactory
     * @inject
     */
    public $newOrderForm;

    /**
     * @var PriceFilter
     * @inject
     */
    public $priceFilter;

    /**
     * @var UserLevelRepository
     * @inject
     */
    public $userLevelRepository;

    /**
     * @var ShippingRepository
     * @inject
     */
    public $shippingRepository;

    private $lostPasswordUserId;


    private function testUser()
    {
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Homepage:default');
        }
    }

    public function actionDefault()
    {
        $galleries = $this->galleryRepository->getAllGalleriesWithFirstImage();
        $marketingGalleries = $this->marketingGalleryRepository->getAllGalleriesWithFirstImage();

        $this->testUser();
        $this->template->orders = $this->orderRepository->getByUser($this->user->id);
        $this->template->rootUrl = $this->linkGenerator->link('Front:Homepage:');
        $this->template->galleries = $galleries;
        $this->template->marketingGalleries = $marketingGalleries;
        $this->template->userId= $this->user->id;
        $this->template->userLevel = $this->user->getIdentity()->user_level_id;
        $this->template->userLevelName = $this->userLevelRepository->getById($this->template->userLevel);
       
        //celkove obraty pro zakazniky
        $now = new DateTime();
        $currencyId = $this->localeRepository->getCurrencyId();
        $this->template->selfMonthTurnover = $this->monthlyCommissionRepository->sumSelfTurnover($this->getUser()->getId(), $now->format('m'), $now->format('Y'), $currencyId);

        $from = new DateTime();
        $to = new DateTime();
        $from->modify('first day of previous month');
        $to->modify('last day of previous month');
        $from->modify('-2 months');
        $from->setTime(0, 0);
        $to->setTime(23, 59, 59);
        $this->template->myTotalTurnover = $this->monthlyCommissionRepository->myTotalTurnover($this->getUser()->getId(), $currencyId);
    }

    public function actionUnpaidOrders()
    {
        $this->template->unpaidOrders = $this->orderRepository->getUnpaidOrders($this->getUser()->getId())->order('id DESC');
    }

    public function actionPaidOrders()
    {
        $this->template->paidOrders = $this->orderRepository->getPaidOrders($this->getUser()->getId())->order('id DESC');
    }

    public function actionProductDog()
    {
        $this->testUser();
        $productDog = $this->productRepository->getProductDog($this->user->id);
        // \Tracy\Debugger::barDump($productDog->fetchPairs('id', 'product_id'));
        $this->template->productDog = $productDog;
        $this->template->products = $this->productRepository->getById($productDog->fetchPairs('id', 'product_id'))->fetchAll();
    }

    public function actionOrder($id)
    {
        $this->testUser();
        $order = $this->orderRepository->getById($id)->fetch();
        /*
        if ($order->user_id != $this->user->id) {
            $this->redirect('Homepage:default');
        }
        */
        $this->template->order = $order;
        $this->template->orderPrice = $order->price;
        $this->template->items = $items = $order->related('order_item')->fetchAll();
        $this->template->shippingPrice = $this->shippingRepository->getPriceById($order->shipping->id, $order->price);
        $this->priceFilter->setCurrency($order->locale->currency->iso);
        $this->priceFilter->setLocale($order->locale->lang->locale);
    }

    public function actionFavorites()
    {
        $favorites = $this->productRepository->getFavorites();
        $this->template->products = $this->productRepository->getById($favorites);
    }

    public function actionDownloads()
    {
        $this->testUser();
        $lang = $this->getParameter('locale');
        $this->template->content = $this->settingRepository->getValue('downloads_' . $lang);
    }

    public function actionMarketingGallery()
    {
        $this->testUser();
        $galleries = $this->marketingGalleryRepository->getAllGalleriesWithFirstImage();
        $this->template->galleries = $galleries;
        $this->template->userLevel = $this->user->getIdentity()->user_level_id;

    }

    public function actionGallery($slug)
    {
        $this->testUser();

        $images = $this->marketingGalleryRepository->getAll($slug);
        if(!$images) {
            throw new BadRequestException();
        }
        $gallery = $this->marketingGalleryRepository->getGalleryById($slug)->fetch();
        $this->template->userLevel = $this->user->getIdentity()->user_level_id;
        $this->template->images = $images;
        $this->template->gallery = $gallery;
    }

    public function createComponentEditForm()
    {
        $form = $this->editForm->create();
        $form->setUserId($this->user->id);
        $form->onDone[] = function () {
            $this->flashMessage('Údaje boli uložené', 'success');
            $this->redirect('this');
        };
        return $form;
    }

    public function handleProductDogRemove($itemId)
    {
        $this->productRepository->removeProductDog($itemId);
        $this->flashMessage('Produkt bol odobraný', 'success');
        $this->redirect('this');
    }

    public function createComponentLostPasswordForm()
    {
        $form = $this->lostPasswordForm->create();
        $form->onDone[] = function () {
            $this->redirect('lostSent');
        };
        return $form;
    }

    public function actionNewPassword($h)
    {
        if (strlen($h) < 1) {
            $this->redirect('Homepage:default');
        }
        $userId = $this->userRepository->checkLostPassword($h);
        if($userId === null) {
            $this->redirect('Homepage:default');
        }
        $this->lostPasswordUserId = $userId;
    }

    public function createComponentNewPasswordForm()
    {
        $form = $this->newPasswordForm->create();
        $form->setUserId($this->lostPasswordUserId);
        $form->onDone[] = function($userId) {
            $this->userRepository->removeLostPassword($userId);
            $this->flashMessage('Heslo bolo zmenené', 'success');
            $this->redirect('Account:default');
        };
        return $form;
    }

    public function createComponentCustomerGrid()
    {
        $grid = $this->customerGrid->create();
        $grid->setParentId($this->user->id);
        $grid->onEdit[] = function ($uid) {
            $this->redirect(':Front:Office:default', ['uid' => $uid]);
        };
        $grid->onDetail[] = function ($uid) {
            $this->redirect(':Front:Office:default', ['uid' => $uid]);
        };
        $grid->onSearch[] = function ($search) {
            $this->redrawControl('searchGrid');
        };
        return $grid;
    }

    public function createComponentCreateUserForm()
    {
        $form = $this->createUserForm->create();
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }

    public function createComponentNewOrderForm()
    {
        $form = $this->newOrderForm->create();
        $form->onDone[] = function () {
            $this->redirect('Cart:default');
        };
        return $form;
    }

}