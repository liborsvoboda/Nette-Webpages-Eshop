<?php


namespace App\AdminModule\Components\UserLevel;


use App\Model\Factory\FormFactory;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class UserLevelForm extends Control
{
    private $userLevelRepository, $formFactory, $id;

    public $onDone = [];

    public function __construct(UserLevelRepository $userLevelRepository, FormFactory $formFactory)
    {
        $this->userLevelRepository = $userLevelRepository;
        $this->formFactory = $formFactory;
    }

    public function setEdit($id)
    {
        $this->id = $id;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addText('name', 'form.title')->setRequired();
        $form->addText('min_turnover', 'form.user.min_turnover')->setRequired();
        $form->addText('min_turnover_cz', 'form.user.min_turnover_cz')->setRequired();
        $form->addText('ord', 'Poradie')->setRequired();
        $form->addText('commission', 'Provízia (%)')->setRequired();
        $form->addSelect('user_group_id', 'form.user.group', $this->userLevelRepository->getGroupsToSelect())
            ->setHtmlAttribute('class', 'select2')
            ->setRequired();
        $form->addSubmit('submit', 'form.save');
        $form->onSuccess[] = [$this, 'formSuccess'];
        if($this->id) {
            $defaults = $this->userLevelRepository->getById($this->id)->fetch();
            $form->setDefaults($defaults);
        }
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $values->min_turnover = str_replace(',', '.', $values->min_turnover);
        $values->commission = str_replace('%', '', $values->commission);
        $this->userLevelRepository->update($values, $this->id);
        $this->onDone();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/template/userLevelForm.latte');
    }

}