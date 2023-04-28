<?php


namespace App\AdminModule\Components\UserLevel;


use App\Model\Factory\FormFactory;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class UserLevelCommissionForm extends Control
{

    public $onDone = [];

    private $formFactory, $userLevelRepository;

    public function __construct(FormFactory $formFactory, UserLevelRepository $userLevelRepository)
    {
        $this->formFactory = $formFactory;
        $this->userLevelRepository = $userLevelRepository;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $tree = $this->getLevelsTree();
        $levels = $this->userLevelRepository->getAll()->order('ord')->fetchAll();
        foreach ($tree as $key => $subs) {
            $commission = json_decode($levels[$key]->commission_level, true);
            foreach ($subs as $sub) {
                $default = $commission[$sub['id']] ?? '';
                $form->addText($key.'s'.$sub['id'], 'l'.$key.'s'.$sub['name'])->setDefaultValue($default);
            }
        }
        $form->addSubmit('submit', 'form.save');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->userLevelRepository->saveUserLevelCommission($values);
        $this->onDone();
    }

    public function getLevelsTree()
    {
        $userLevels = $this->userLevelRepository->getAll()->order('ord');
        foreach ($userLevels as $userLevel) {
            $subs = $this->userLevelRepository->getSubLevels($userLevel->id);
            $tree[$userLevel->id] = $subs;
        }
        return $tree;
    }

    public function render()
    {
        $this->template->levels = $this->userLevelRepository->getAll()->fetchAll();
        $this->template->tree = $this->getLevelsTree();
        $this->template->render(__DIR__.'/templates/userLevelCommissionForm.latte');
    }
}