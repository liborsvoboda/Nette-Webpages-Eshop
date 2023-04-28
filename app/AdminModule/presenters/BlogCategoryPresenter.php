<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\BlogCategory\IBlogCategoryGridFactory;
use App\AdminModule\Components\BlogCategory\IBlogCategoryFormFactory;

class BlogCategoryPresenter extends ContentPresenter {

    /**
     * @var IBlogCategoryGridFactory
     * @inject
     */
    public $blogCategoryGrid;
    
      /**
     * @var IBlogCategoryFormFactory
     * @inject
     */
    public $blogCategoryForm;
    
    protected $editId = null;
    
    public function actionEdit($id)
    {
        $this->editId = $this->getParameter('id');
    }

    public function createComponentBlogCategoryGrid() {
        $grid = $this->blogCategoryGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        return $grid;
    }
    

    public function createComponentBlogCategoryAddForm() {
        $form = $this->blogCategoryForm->create();
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }
    
    public function createComponentBlogCategoryEditForm()
    {
        $form = $this->blogCategoryForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }

}
