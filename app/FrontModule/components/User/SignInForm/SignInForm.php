<?php


namespace App\FrontModule\Components\User;


use App\Model\Cart\CartRepository;
use App\Model\Factory\FormFactory;
use App\Model\Services\AppSettingsService;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

class SignInForm extends Control
{
    public $user,
        $appSettingsService,
        $formFactory,
        $userRepository,
        $onDone = [],
        $onError = [];

    public function __construct(User $user, AppSettingsService $appSettingsService, FormFactory $formFactory, UserRepository $userRepository)
    {
        $this->user = $user;
        $this->appSettingsService = $appSettingsService;
        $this->formFactory = $formFactory;
        $this->userRepository = $userRepository;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addText('email','strings.email-placeholder')->setRequired();
        $form->addPassword('password',"strings.password-placeholder")->setRequired();
        $form->addCheckbox('remember');
        $form->addSubmit('submit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form, ArrayHash $values)
    {
        try {
            $this->user->setExpiration('14 days');
            $this->user->login($values->email, $values->password);
            $this->userRepository->copyInfoToSession();
            $this->onDone();
        } catch (AuthenticationException $e) {
            $this->onError();
        }
    }

    public function render()
    {
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/Sign/signInForm.latte');
    }

}