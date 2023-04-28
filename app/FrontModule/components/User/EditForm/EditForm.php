<?php


namespace App\FrontModule\Components\User;


use App\Model\Factory\FormFactory;
use App\Model\Services\AppSettingsService;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;

class EditForm extends Control
{
    private $appSettingsService, $userRepository, $userId, $formFactory, $translator;

    public $onDone = [];

    public function __construct(AppSettingsService $appSettingsService, UserRepository $userRepository, FormFactory $formFactory, ITranslator $translator)
    {
        $this->appSettingsService = $appSettingsService;
        $this->userRepository = $userRepository;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->setTranslator($this->translator);

        $form->addText('email');

        $form->addCheckbox('isCompany', 'cart.address.b2b_request');
        $form->addText('firstName', 'cart.address.name')->setRequired('form.required');
        $form->addText('lastName', 'cart.address.surname')->setRequired('form.required');
        $form->addText('street', 'cart.address.street')->setRequired('form.required');
        $form->addText('city', 'cart.address.city')->setRequired('form.required');
        $form->addText('zip', 'cart.address.zip')->setRequired('form.required');
        $form->addText('phone', 'cart.address.phone')->setRequired('form.required');
        $form->addText('companyName', 'cart.address.company')->addConditionOn($form['isCompany'], Form::EQUAL, true)->setRequired('form.required');
        $form->addText('ico', 'cart.address.ico')->addConditionOn($form['isCompany'], Form::EQUAL, true)->setRequired('form.required');
        $form->addText('dic', 'cart.address.dic');
        $form->addText('icdph', 'cart.address.icdph');

        $form->addSubmit('submit', 'strings.save');
        $form->onSuccess[] = [$this, 'formSuccess'];
        $usr = $this->userRepository->getById($this->userId)->fetch();
        $form->setDefaults($usr);
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->userRepository->update($this->userId, $values);
        $this->userRepository->copyInfoToSession();
        $this->flashMessage('form.saved_success', 'success');
        $this->onDone();
    }

    public function render()
    {
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/User/UserEdit/userEdit.latte');
    }

}