<?php


namespace App\FrontModule\Components\User;


use App\Model\Factory\FormFactory;
use App\Model\Services\AppSettingsService;
use App\Model\Services\TplSettingsService;
use App\Model\User\UserRepository;
use App\Model\Country\CountryRepository;
use App\Model\Services\EkosystemService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Nette\Localization\ITranslator;
use Nette\Application\Responses\JsonResponse;
use DragonBe\Vies\Vies;
use DragonBe\Vies\ViesException;
use DragonBe\Vies\ViesServiceException;
use h4kuna\Ares\Ares;
use h4kuna\Ares\Exceptions\IdentificationNumberNotFoundException;
use Exception;

class RegisterForm extends Control
{
    public $user,
        $appSettingsService,
        $tplSettingsService,
        $userRepository,
        $countryRepository,
        $formFactory,
        $translator,
        $ekosystemService,
        $locale,
        $onDone = [],
        $onError = [];

    public function __construct(
        string $locale,
        User $user,
        EkosystemService $ekosystemService,
        AppSettingsService $appSettingsService,
        TplSettingsService $tplSettingsService,
        UserRepository $userRepository,
        CountryRepository $countryRepository,
        FormFactory $formFactory,
        ITranslator $translator
    ) {
        $this->locale = $locale;
        $this->user = $user;
        $this->appSettingsService = $appSettingsService;
        $this->tplSettingsService = $tplSettingsService;
        $this->userRepository = $userRepository;
        $this->countryRepository = $countryRepository;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
        $this->ekosystemService = $ekosystemService;
    }

    public function createComponentForm()
    {

        $countries = $this->countryRepository->getForSelect();

        $form = $this->formFactory->create();
        $form->setTranslator($this->translator);
        $form->addText('email', 'strings.email')->setRequired('form.required');
        $form->addPassword('password', 'strings.password')->setRequired('form.required');
        $form->addPassword('rpassword', 'strings.password_repeat')->setRequired('form.required')->addRule(Form::EQUAL, 'form.password_not_match', $form['password']);
        $form->addText('parent_ref_no', 'strings.referral')->setDefaultValue($this->userRepository->getRefNoFromSession());
        $form->addText('iban', 'cart.address.iban');
        $form->addCheckbox('b2bRequest', 'cart.address.b2b_request');
        $form->addText('firstName', 'cart.address.name')->addConditionOn($form['b2bRequest'], Form::EQUAL, true)->setRequired('form.required');
        $form->addText('lastName', 'cart.address.surname')->addConditionOn($form['b2bRequest'], Form::EQUAL, true)->setRequired('form.required');
        $form->addText('companyName', 'cart.address.company')->addConditionOn($form['b2bRequest'], Form::EQUAL, true)->setRequired('form.required');
        $form->addText('street', 'cart.address.street')->addConditionOn($form['b2bRequest'], Form::EQUAL, true)->setRequired('form.required');
        $form->addText('city', 'cart.address.city')->addConditionOn($form['b2bRequest'], Form::EQUAL, true)->setRequired('form.required');
        $form->addText('zip', 'cart.address.zip')->addConditionOn($form['b2bRequest'], Form::EQUAL, true)->setRequired('form.required');
        $form->addSelect('countryCode', 'cart.address.country', $countries)->setPrompt('cart.address.country')->setRequired('cart.validation.country');
        $form->addText('ico', 'cart.address.ico')
            ->setOption('allowSearch', in_array(key($countries), ['cz', 'sk']))
            ->addConditionOn($form['b2bRequest'], Form::EQUAL, true)->setRequired('form.required');
        $form->addText('dic', 'cart.address.dic');
        $form->addText('icdph', 'cart.address.icdph');
        $form->addText('phone', 'cart.address.phone')->addConditionOn($form['b2bRequest'], Form::EQUAL, true)->setRequired('form.required');
        $form->addCheckbox('gdpr', 'form.gdpr_consent')->setRequired('form.gdpr_required');

        $form->setDefaults(['countryCode' => key($countries)]);
        $form->addSubmit('submit');
        $form->onValidate[] = [$this, 'validateForm'];
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function validateForm(Form $form)
    {
        $values = $form->getValues();
        $countryCode = Strings::upper($values->countryCode);
        if ($values->b2bRequest && in_array($countryCode, ['CZ', 'SK'])) {
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

    public function formSuccess(Form $form, ArrayHash $values)
    {
        try {
            $this->userRepository->addRegister($values);
            $this->onDone();
        } catch (AuthenticationException $e) {
            $this->onError($e);
        }
    }

    public function handleAres()
    {
        $ic = (isset($_GET['ic'])) ? trim($_GET['ic']) : null;
        $country = (isset($_GET['country'])) ? trim($_GET['country']) : null;

        $result = [
            'error' => $this->translator->translate('form.ic_not_found'),
            'success' => false
        ];
        if ($country == 'sk') {
            try {
                $result['data'] = $this->ekosystemService->loadData($ic);
                $result['error'] = null;
                $result['success'] = true;
            } catch (Exception $e) { }
        } else if ($country == 'cz') {
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
            } catch (Exception $e) { }
        }
        return $this->presenter->sendResponse(new JsonResponse($result));
    }

    public function render()
    {
        $this->template->tplSetting = $this->tplSettingsService;
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/User/RegisterForm/registerForm.latte');
    }

}