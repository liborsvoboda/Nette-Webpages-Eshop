<?php


namespace App\FrontModule\Components\User;


use App\Model\Factory\FormFactory;
use App\Model\Services\AppSettingsService;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class LostPasswordForm extends Control
{
    private $userRepository, $appSettingsService, $formFactory;

    public $onDone = [];

    public function __construct(UserRepository $userRepository, AppSettingsService $appSettingsService, FormFactory $formFactory)
    {
        $this->userRepository = $userRepository;
        $this->appSettingsService = $appSettingsService;
        $this->formFactory = $formFactory;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addEmail('email')->setRequired('Zadajte email');
        $form->addSubmit('submit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->userRepository->sendLostPassword($values->email);
        $this->onDone();
    }

    public function render()
    {
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/User/LostPasswordForm/lostPasswordForm.latte');
    }
}