<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Page\IPageFormFactory;
use App\AdminModule\Components\Page\IPageGridFactory;

class PagePresenter extends ContentPresenter
{
    /**
     * @var IPageGridFactory
     * @inject
     */
    public $pageGrid;

    /**
     * @var IPageFormFactory
     * @inject
     */
    public $pageForm;

    protected $editId = null;

    public function actionEdit($id)
    {
        $this->editId = $this->getParameter('id');
    }


    public function createComponentPageGrid()
    {
        $grid = $this->pageGrid->create();
        $grid->setLocaleId($this->loc);
        $grid->onLocaleChange[] = function ($localeId) {
            $this->loc = $localeId;
        };
        $grid->onDone[] = function () {
            $this->redirect('this');
        };
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        return $grid;
    }

    public function createComponentPageForm()
    {
        $form = $this->pageForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }
}