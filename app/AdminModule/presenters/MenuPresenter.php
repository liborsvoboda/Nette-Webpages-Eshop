<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Menu\IMenuFormFactory;
use App\AdminModule\Components\Menu\IMenuGridFactory;

class MenuPresenter extends ContentPresenter
{
    /**
     * @var IMenuGridFactory
     * @inject
     */
    public $menuGrid;

    /**
     * @var IMenuFormFactory
     * @inject
     */
    public $menuForm;

    private $menuId = null;

    public function createComponentMenuGrid()
    {
        $grid = $this->menuGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        return $grid;
    }

    public function actionEdit($menuId)
    {
        $this->menuId = $menuId;
    }

    public function createComponentMenuForm()
    {
        $form = $this->menuForm->create();
        $form->setEdit($this->menuId);
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }

}