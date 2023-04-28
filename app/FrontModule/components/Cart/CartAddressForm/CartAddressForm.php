<?php


namespace App\FrontModule\Components\Cart;


use App\Model\Cart\CartRepository;
use App\Model\Control\BaseControl;
use App\Model\Country\CountryRepository;
use App\Model\Factory\FormFactory;
use App\Model\LocaleRepository;
use App\Model\Order\OrderRepository;
use App\Model\Payment\PaymentRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\TplSettingsService;
use App\Model\Shipping\ShippingRepository;
use App\Model\Services\EkosystemService;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;
use Nette\Utils\Strings;
use DragonBe\Vies\Vies;
use DragonBe\Vies\ViesException;
use DragonBe\Vies\ViesServiceException;
use h4kuna\Ares\Ares;
use h4kuna\Ares\Exceptions\IdentificationNumberNotFoundException;


class CartAddressForm extends BaseControl
{
    private $appSettingsService,
        $tplSettingsService,
        $cartRepository,
        $shippingRepository,
        $paymentRepository,
        $orderRepository,
        $countryRepository,
        $ekosystemService,
        $country = null,
        $otherCountry = null,
        $shipping = null,
        $payment = null,
        $formFactory,
        $localeRepository,
        $locale;
    public $onDone = [];

    public function __construct(
        AppSettingsService $appSettingsService,
        TplSettingsService $tplSettingsService,
        CartRepository $cartRepository,
        ShippingRepository $shippingRepository,
        PaymentRepository $paymentRepository,
        OrderRepository $orderRepository,
        CountryRepository $countryRepository,
        FormFactory $formFactory,
        EkosystemService $ekosystemService,
        LocaleRepository $localeRepository,
        ITranslator $translator
    )
    {
        $this->cartRepository = $cartRepository;
        $this->appSettingsService = $appSettingsService;
        $this->tplSettingsService = $tplSettingsService;
        $this->shippingRepository = $shippingRepository;
        $this->paymentRepository = $paymentRepository;
        $this->orderRepository = $orderRepository;
        $this->countryRepository = $countryRepository;
        $this->formFactory = $formFactory;
        $this->ekosystemService = $ekosystemService;
        $this->localeRepository = $localeRepository;
        $this->translator = $translator;
    }

    public function setLocale($locale) {
        $this->locale = $locale;
        return $this;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $cartPrice = $this->cartRepository->getTotalPrice();
        $weight = $this->cartRepository->calculateWeight();
        $form->addText('firstName')->setRequired('cart.validation.name');
        $form->addText('lastName')->setRequired('cart.validation.surname');
        $form->addEmail('email')->setRequired('cart.validation.email');
        $form->addText('street')->setRequired('cart.validation.street');
        $form->addText('city')->setRequired('cart.validation.city');
        $form->addText('zip')->setRequired('cart.validation.zip');
        $form->addText('phone')->setRequired('cart.validation.phone');
        $form->addTextArea('note');
        $form->addCheckbox('otherAddress');
        $form->addText('otherName')->addConditionOn($form['otherAddress'], Form::EQUAL, true)->setRequired('cart.validation.name');
        $form->addText('otherSurname')->addConditionOn($form['otherAddress'], Form::EQUAL, true)->setRequired('cart.validation.surname');
        $form->addText('otherStreet')->addConditionOn($form['otherAddress'], Form::EQUAL, true)->setRequired('cart.validation.street');
        $form->addText('otherCity')->addConditionOn($form['otherAddress'], Form::EQUAL, true)->setRequired('cart.validation.city');
        $form->addText('otherZip')->addConditionOn($form['otherAddress'], Form::EQUAL, true)->setRequired('cart.validation.zip');
        $form->addText('otherPhone')->addConditionOn($form['otherAddress'], Form::EQUAL, true)->setRequired('cart.validation.phone');
        $form->addCheckbox('isCompany');
        $form->addText('companyName')->addConditionOn($form['isCompany'], Form::EQUAL, true)->setRequired('cart.validation.company');
        $form->addText('ico')->addConditionOn($form['isCompany'], Form::EQUAL, true)->setRequired('cart.validation.ico');
        $form->addText('dic');
        $form->addText('icdph');
        $defaults = $this->orderRepository->getDataFromSession();
        if (isset($defaults['country'])) {
            $this->country = $defaults['country'];
        }
        if (isset($defaults['shipping'])) {
            $this->shipping = $defaults['shipping'];
        }
        if (isset($defaults['payment'])) {
            $this->payment = $defaults['payment'];
        }

        $defaults['country'] = $this->localeRepository->getCountryid($this->locale);
        $defaults['otherCountry'] = $this->localeRepository->getCountryid($this->locale);
        $this->country = $defaults['country'];
        $this->otherCountry = $defaults['otherCountry'];
        $form->addSelect('country', 'cart.address.country', $this->countryRepository->getForSelect($this->country, 'id', 'id'))->setPrompt('cart.address.country')->setRequired('cart.validation.country');
        $form->addSelect('otherCountry', 'cart.address.country', $this->countryRepository->getForSelect($this->otherCountry, 'id', 'id'))->setPrompt('cart.address.country')->setRequired('cart.validation.country');
        $shippings = $this->shippingRepository->getRelevantMethodsForSelect($this->country, $cartPrice);
        $form->addRadioList('shipping', 'cart.delivery', $shippings)->setRequired('cart.validation.shipping');
        $payments = $this->paymentRepository->getRelevantMethodsForSelect($this->shipping);
        $form->addRadioList('payment', 'cart.payment', $payments)
            ->setRequired('cart.validation.payment');
        if(isset($defaults['shipping']) && !in_array($defaults['shipping'], $shippings)) {
            unset($defaults['shipping']);
        }
        if(isset($defaults['shipping']) && !in_array($defaults['payment'], $payments)) {
            unset($defaults['payment']);
        }
        $form->setDefaults($defaults);
        $form->addSubmit('submit');
        $form->getElementPrototype()->id = 'frm-cartAddress';
//        $form->onValidate[] = [$this, 'validateForm'];
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function setCountry($countryId)
    {
        $this->country = $countryId;
    }

    public function setOtherCountry($countryId)
    {
        $this->otherCountry = $countryId;
    }

    public function setShipping($shippingId)
    {
        $this->shipping = $shippingId;
    }

    public function validateForm(Form $form)
    {
        $values = $form->getValues();
        if ($values->isCompany) {
            $countryCode = Strings::upper($this->countryRepository->getAll()->where('id', $values->country)->fetch()->code);
            $vat = ($countryCode === 'CZ') ? $values->dic : $values->icdph;
            $vat = preg_replace('/^' . $countryCode . '(.+)$/', '$1', $vat);

            if (trim($vat) !== '') {
                $vies = new Vies();
                if ($vies->getHeartBeat()->isAlive() === FALSE) {
                    $form->addError('form.vies.not_available');
                }
                try {
                    $result = $vies->validateVat($countryCode, $vat);
                } catch (ViesException $e) {
                    $form->addError('form.vies.country_not_match');
                } catch (ViesServiceException $e) {
                    $form->addError('form.vies.not_available');
                }

                if (!$result->isValid()) {
                    $form->addError('form.vies.invalid');
                }
            }
        }
    }

    public function formSuccess(Form $form)
    {
        $this->orderRepository->saveDataToSession($form->getValues(true));
        $this->onDone();
    }

    public function handleAres()
    {
        $ic = (isset($_GET['ic'])) ? trim($_GET['ic']) : null;
        $country = (isset($_GET['country'])) ? trim($_GET['country']) : null;

        $result = [
            'error' => $this->translator->translate('form.ic_not_found'),
            'success' => false
        ];
        if ($country == 1) {
            try {
                $result['data'] = $this->ekosystemService->loadData($ic);
                $result['error'] = null;
                $result['success'] = true;
            } catch (\Exception $e) { }
        } else if ($country == 2) {
            try {
                $ares = new Ares();
                $res = $ares->loadData($ic);

                $data['tin'] = $res->tin;
                $data['name'] = $res->company;
                $data['formatted_street'] = $res->street . ' ' . $res->house_number;
                $data['municipality'] = $res->city_post . ' - ' . $res->city_district;
                $data['postal_code'] = $res->zip;

                $result['data'] = $data;
                $result['error'] = null;
                $result['success'] = true;
            } catch (\Exception $e) { }
        }
        return $this->presenter->sendResponse(new JsonResponse($result));
    }

    public function render()
    {
        $defaults = $this->orderRepository->getDataFromSession();
        if (isset($defaults['payment'])) {
            $this->payment = $defaults['payment'];
        }
        $this->template->tplSetting = $this->tplSettingsService;
        $this->template->payment = $this->payment;
        $this->template->shipping = $this->shipping;
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir() . '/Cart/CartAddressForm/cartAddressForm.latte');
    }

}