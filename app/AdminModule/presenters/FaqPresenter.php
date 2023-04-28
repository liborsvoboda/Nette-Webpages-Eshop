<?php


namespace App\AdminModule\Presenters;



use App\AdminModule\Components\Faq\IFaqFormFactory;
use App\AdminModule\Components\Faq\IFaqGridFactory;

class FaqPresenter extends ContentPresenter
{
    /**
     * @var IFaqGridFactory
     * @inject
     */
    public $faqGrid;

    /**
     * @var IFaqFormFactory
     * @inject
     */
    public $faqForm;

    protected $editId = null;

    public function actionEdit($id)
    {
        $this->editId = $this->getParameter('id');
    }

    public function createComponentFaqGrid()
    {
        $grid = $this->faqGrid->create();
        $grid->setLocaleId($this->loc);
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        $grid->onLocaleChange[] = function ($localeId) {
            $this->loc = $localeId;
        };
        return $grid;
    }

    public function createComponentFaqAddForm()
    {
        $form = $this->faqForm->create();
        $form->setLocale($this->loc);
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }

    public function createComponentFaqEditForm()
    {
        $form = $this->faqForm->create();
        $form->editId = $this->editId;
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }
}