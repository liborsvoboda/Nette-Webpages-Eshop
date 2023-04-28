<?php


namespace App\FrontModule\Components\User;


use App\Model\Factory\FormFactory;
use App\Model\Services\AppSettingsService;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

class NewPasswordForm extends Control
{
    public $user,
        $appSettingsService,
        $userRepository,
        $formFactory,
        $onDone = [],
        $onError = [];

    private $userId;

    public function __construct(User $user, AppSettingsService $appSettingsService, UserRepository $userRepository, FormFactory $formFactory)
    {
        $this->user = $user;
        $this->appSettingsService = $appSettingsService;
        $this->userRepository = $userRepository;
        $this->formFactory = $formFactory;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addPassword('password')->setRequired();
        $form->addPassword('rpassword')->setRequired()->addRule(Form::EQUAL, 'Hesla nesúhlasia.', $form['password']);
        $form->addSubmit('submit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form, ArrayHash $values)
    {
        try {
            $this->userRepository->changePassword($this->userId, $values->password);
            $user = $this->userRepository->getById($this->userId)->fetch();
            $this->user->setExpiration('14 days');
            $this->user->login($user->email, $values->password);
            $this->onDone($this->userId);
        } catch (AuthenticationException $e) {
            $this->onError($e);
        }
    }

    public function render()
    {
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/User/NewPasswordForm/newPasswordForm.latte');
    }

}