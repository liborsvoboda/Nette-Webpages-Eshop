<?php


namespace App\AdminModule\Components\Customer;


use App\Model\Services\UserManager;
use App\Model\User\UserRepository;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class CustomerForm extends Control

{
    private $userRepository, $userId = null, $userLevelRepository;

    public $onDone = [];

    public function __construct(UserRepository $userRepository, UserLevelRepository $userLevelRepository)
    {
        $this->userRepository = $userRepository;
        $this->userLevelRepository = $userLevelRepository;
    }

    public function setEdit($userId)
    {
        $this->userId = $userId;
    }

    public function createComponentForm()
    {
        $form = new Form();
        $userData = $this->userRepository->getById($this->userId)->fetch();
        $userLevel = $this->userLevelRepository->getAll()->fetchPairs('id', 'name');
        $form->addText('email', 'E-mail')->setHtmlAttribute('readonly', 'readonly');
        $form->addText('firstName', 'Jméno');
        $form->addText('lastName', 'Příjmení');
        $form->addText('street', 'Ulice, č. p.');
        $form->addText('city', 'Město');
        $form->addText('zip', 'PSČ');
        $form->addText('companyName', 'Název firmy');
        $form->addText('ico', 'IČO');
        $form->addText('dic', 'DIČ');
        $form->addText('icdph', 'IČDPH');
        $form->addText('user_level_id', 'Zákaznická skupina')->setDisabled();
        $form->addSubmit('submit', 'Uložit');
        $form->setDefaults($userData);
        $form['user_level_id']->setDefaultValue($userLevel[$userData->user_level_id]);
        $form->onSuccess[] = [$this, 'formSuccess'];
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->userRepository->update($this->userId, $values);
        $this->onDone();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/customerForm.latte');
    }

}