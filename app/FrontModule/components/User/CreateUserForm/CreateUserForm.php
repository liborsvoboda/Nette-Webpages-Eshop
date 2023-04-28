<?php

namespace App\FrontModule\Components\User;

use App\Model\Country\CountryRepository;
use App\Model\Factory\FormFactory;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\DateTime;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class CreateUserForm extends Control
{
    private FormFactory $formFactory;
    private UserRepository $userRepository;
    private User $user;
    private CountryRepository $countryRepository;

    public $onDone = [];

    public function __construct(FormFactory $formFactory, UserRepository $userRepository, User $user, CountryRepository $countryRepository)
    {

        $this->formFactory = $formFactory;
        $this->userRepository = $userRepository;
        $this->user = $user;
        $this->countryRepository = $countryRepository;
    }

    public function createComponentForm()
    {
        $countries = $this->countryRepository->getForSelect();
        $form = $this->formFactory->create();
        $form->addText('email', 'strings.email')->setRequired('form.required');
        $form->addPassword('password', 'strings.password')->setRequired('form.required');
        $form->addPassword('rpassword', 'strings.password_repeat')->setRequired('form.required')->addRule(Form::EQUAL, 'form.password_not_match', $form['password']);
        $form->addText('ref_no', 'strings.referral')->setDefaultValue($this->userRepository->getActualUserRefNo($this->user->getId()));
        $form->addText('firstName', 'cart.address.name');
        $form->addText('lastName', 'cart.address.surname');
        $form->addText('street', 'cart.address.street');
        $form->addText('city', 'cart.address.city');
        $form->addText('zip', 'cart.address.zip');
        $form->addText('phone', 'cart.address.phone');
        $form->addSelect('country_id', 'cart.address.country', $countries)->setPrompt('cart.address.country');//->setRequired('cart.validation.country');

        $form->addCheckbox('isCompany', 'cart.address.b2b_request')
                ->addCondition($form::EQUAL, true)
                ->toggle('companyPart');
        $form->addText('companyName', 'cart.address.company')
                ->setOption('id', 'companyPart');
        $form->addText('iban', 'cart.address.iban')
                ->setOption('id', 'companyPart');
        $form->addText('ico', 'cart.address.ico')
                ->setOption('id', 'companyPart');
        $form->addText('dic', 'cart.address.dic')
                ->setOption('id', 'companyPart');
        $form->addText('icdph', 'cart.address.icdph')
                ->setOption('id', 'companyPart');

        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        $form->addSubmit('submit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $values->referral_id = $this->user->getId();
        $values->registered_at = new DateTime();
        $this->userRepository->addFromReferral($values);
        $this->onDone();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/createUserForm.latte');
    }

}