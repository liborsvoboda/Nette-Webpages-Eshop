<?php


namespace App\FrontModule\Presenters;


use App\FrontModule\Components\User\IRegisterFormFactory;
use App\Model\User\UserRepository;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Utils\ArrayHash;

class SignPresenter extends BasePresenter
{

    /**
     * @var IRegisterFormFactory
     * @inject
     */
    public $registerForm;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    public function startup()
    {
        parent::startup();
    }

    public function createComponentSignInForm()
    {
        $form = new Form();
        $form->setTranslator($this->translator);
        $form->addText('email')->setRequired();
        $form->addPassword('password')->setRequired();
        $form->addCheckbox('remember');
        $form->addSubmit('submit');
        $form->onSuccess[] = [$this, 'signInSuccess'];
        return $form;
    }

    public function signInSuccess(Form $form, ArrayHash $values)
    {
        try {
            $this->user->setExpiration('14 days');
            $this->user->login($values->email, $values->password);
            $this->cartRepository->recalculateCartWhenSignIn();
            $this->userRepository->copyInfoToSession();
            $this->redirect('Account:default');
        } catch (AuthenticationException $e) {
            $form->addError('The username or password you entered is incorrect.');
            $this->flashMessage('Zadali ste zle e-mail alebo heslo', 'danger');
            $this->redirect('this');
        }
    }

    public function actionIn()
    {
        if($this->user->isLoggedIn()) {
            $this->redirect('Account:default');
        }
    }

    public function actionUp()
    {
        if($this->user->isLoggedIn()) {
            $this->redirect('Homepage:default');
        }
    }

    public function actionOut()
    {
        $this->user->logout();
        $this->cartRepository->recalculateCartWhenSignOut();
        $this->redirect('Homepage:default');
    }

    public function createComponentRegisterForm()
    {
        $form = $this->registerForm->create($this->getParameter('locale'));
        $form->onDone[] = function () {
            $this->redirect('Homepage:default');
        };
        $form->onError[] = function (AuthenticationException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
            $this->redirect('this');
        };
        return $form;
    }
}