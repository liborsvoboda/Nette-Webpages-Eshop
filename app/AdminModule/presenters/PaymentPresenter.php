<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Payment\IPaymentFormFactory;
use App\AdminModule\Components\Payment\IPaymentGridFactory;
use App\AdminModule\Components\Shipping\IShippingFormFactory;
use App\AdminModule\Components\Shipping\IShippingGridFactory;

class PaymentPresenter extends SettingPresenter
{

    /**
     * @var IPaymentGridFactory
     * @inject
     */
    public $paymentGrid;

    /**
     * @var IPaymentFormFactory
     * @inject
     */
    public $paymentForm;

    public $editId = null;

    public function actionEdit($id)
    {
        $this->editId = $id;
    }

    public function createComponentPaymentGrid()
    {
        $grid = $this->paymentGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        return $grid;
    }

    public function createComponentPaymentForm()
    {
        $form = $this->paymentForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }
}