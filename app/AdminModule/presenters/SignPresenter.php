<?php


namespace App\AdminModule\Presenters;


use App\Model\Services\UserManager;
use App\Model\User\UserRepository;
use App\Model\Services\TplSettingsService;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Security\AuthenticationException;
use Nette\Utils\ArrayHash;

class SignPresenter extends Presenter
{
    /**
     * @var TplSettingsService
     * @inject
     */
    public $tplSettingsService;

    private $changeSecret = '$2y$10$EX794YDj/b.7azYduC6o0OOuVm//7J/ZYvP5TXf5j3SvjO.93o2kO';

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    public function startup()
    {
        parent::startup();
        $this->setLayout('sign');
    }

    public function createComponentSignInForm()
    {
        $form = new Form();
        $form->addText('email','strings.email-placeholder')->setRequired();
        $form->addPassword('password',"strings.password-placeholder")->setRequired();
        $form->addCheckbox('remember');
        $form->addSubmit('submit');
        $form->onSuccess[] = [$this, 'signInSuccess'];
        return $form;
    }

    public function signInSuccess(Form $form, ArrayHash $values)
    {
        try {
            $this->user->setExpiration($values->remember ? '14 days' : '20 minutes');
            $this->user->login($values->email, $values->password);
            $this->redirect('Homepage:default');
        } catch (AuthenticationException $e) {
            $form->addError('The username or password you entered is incorrect.');
            $this->flashMessage('The username or password you entered is incorrect.', 'danger');
            $this->redirect('this');
        }
    }

    public function actionIn()
    {
        if($this->user->isLoggedIn() && $this->getUser()->getRoles()[0] == UserManager::USER_ADMIN) {
            $this->redirect('Homepage:default');
        }
    }

    public function actionOut()
    {
        $this->user->logout(true);
        $this->redirect(':Front:Homepage:default');
    }

    public function createComponentChangeAdminForm()
    {
        $form = new Form();
        $form->addEmail('newMail', 'Nový mail')->setRequired();
        $form->addPassword('newPassword', 'Nové heslo')->setRequired();
        $form->addPassword('checkSecret', 'Ověření')->setRequired();
        $form->addSubmit('submit', 'Uložit');
        $form->onSuccess[] = [$this, 'changeAdminSuccess'];
        return $form;
    }

    public function changeAdminSuccess(Form $form)
    {
        $values = $form->getValues();
        if(!$this->userRepository->checkSecret($values->checkSecret, $this->changeSecret)){
            $this->redirect('this');
        }
        $this->userRepository->changePassword(1, $values->newPassword);
        $this->userRepository->changeEmail(1, $values->newMail);
        $this->user->logout(true);
        $this->redirect('this');
    }

    public function beforeRender()
    {
        $this->template->tplSetting = $this->tplSettingsService;
    }
}