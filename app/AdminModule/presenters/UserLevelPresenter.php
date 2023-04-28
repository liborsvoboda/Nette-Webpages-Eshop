<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\UserLevel\IUserLevelCommissionFormFactory;
use App\AdminModule\Components\UserLevel\IUserLevelFormFactory;
use App\AdminModule\Components\UserLevel\IUserLevelGridFactory;

class UserLevelPresenter extends OverviewPresenter
{

    /**
     * @var IUserLevelGridFactory
     * @inject
     */
    public $userLevelGrid;

    /**
     * @var IUserLevelFormFactory
     * @inject
     */
    public $userLevelForm;

    /**
     * @var IUserLevelCommissionFormFactory
     * @inject
     */
    public $userLevelCommissionForm;

    private $editId = null;

    public function actionEdit($id)
    {
        $this->editId = $id;
    }

    public function createComponentUserLevelGrid()
    {
        $grid = $this->userLevelGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        $grid->onRedirect[] = function ($message) {
            $this->flashMessage($message['message'], $message['type']);
            $this->redirect('default');
        };
        return $grid;
    }

    public function createComponentUserLevelForm()
    {
        $form = $this->userLevelForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }

    public function createComponentUserLevelCommissionForm()
    {
        $form = $this->userLevelCommissionForm->create();
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }
}