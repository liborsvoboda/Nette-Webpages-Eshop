<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Customer\ICustomerFormFactory;
use App\AdminModule\Components\Customer\ICustomerGridFactory;

class CustomerPresenter extends AdminPresenter
{
    /**
     * @var ICustomerGridFactory
     * @inject
     */
    public $customerGrid;

    /**
     * @var ICustomerFormFactory
     * @inject
     */
    public $customerForm;

    protected $editId = null;

    public function actionEdit($id)
    {
        $this->editId = $id;
    }

    public function createComponentCustomerGrid()
    {
        $grid = $this->customerGrid->create();
        $grid->setFrom($this->from);
        $grid->setTo($this->to);
        $grid->onEdit[] = function ($customerId) {
            $this->redirect('edit', $customerId);
        };
        $grid->onLoginAs[] = function ($id) {
            $this->redirect(':Front:Homepage:loginas', $id);
        };
        return $grid;
    }

    public function createComponentCustomerForm()
    {
        $form = $this->customerForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }
}