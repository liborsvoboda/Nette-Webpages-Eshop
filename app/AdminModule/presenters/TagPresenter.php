<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\Tag\ITagGridFactory;
use App\AdminModule\Components\Tag\ITagFormFactory;

class TagPresenter extends ContentPresenter {

    /**
     * @var ITagGridFactory
     * @inject
     */
    public $tagGrid;
    
    /**
     * @var ITagFormFactory
     * @inject
     */
    public $tagForm;
    
    protected $editId = null;

    public function actionEdit($id)
    {
        $this->editId = $this->getParameter('id');
    }

    public function createComponentTagGrid() {
        $grid = $this->tagGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        return $grid;
    }
    
    public function createComponentTagAddForm()
    {
        $form = $this->tagForm->create();
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }
    
    public function createComponentTagEditForm()
    {
        $form = $this->tagForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }
}
