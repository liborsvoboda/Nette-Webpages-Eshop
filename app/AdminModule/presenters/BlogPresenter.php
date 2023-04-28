<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Blog\IBlogFormFactory;
use App\AdminModule\Components\Blog\IBlogGridFactory;

class BlogPresenter extends ContentPresenter
{
    /**
     * @var IBlogGridFactory
     * @inject
     */
    public $blogGrid;

    /**
     * @var IBlogFormFactory
     * @inject
     */
    public $blogForm;

    protected $editId = null;

    public function actionEdit($id)
    {
        $this->editId = $this->getParameter('id');
    }

    public function createComponentBlogGrid()
    {
        $grid = $this->blogGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        return $grid;
    }

    public function createComponentBlogAddForm()
    {
        $form = $this->blogForm->create();
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }

    public function createComponentBlogEditForm()
    {
        $form = $this->blogForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }
}