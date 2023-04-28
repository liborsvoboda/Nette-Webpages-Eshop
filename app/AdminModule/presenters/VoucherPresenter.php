<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Voucher\IVoucherFormFactory;
use App\AdminModule\Components\Voucher\IVoucherGridFactory;

class VoucherPresenter extends CataloguePresenter
{
    protected $editId = null;

    /**
     * @var IVoucherGridFactory
     * @inject
     */
    public $voucherGrid;

    /**
     * @var IVoucherFormFactory
     * @inject
     */
    public $voucherForm;

    public function createComponentVoucherGrid()
    {
        $grid = $this->voucherGrid->create();
        $grid->onEdit[] = function ($voucher_id) {
            $this->redirect('edit', $voucher_id);
        };
        return $grid;
    }

    public function createComponentVoucherForm()
    {
        $form = $this->voucherForm->create();
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }

    public function createComponentVoucherEditForm()
    {
        $form = $this->voucherForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }

    public function actionEdit($id)
    {
        $this->editId = $id;
    }
}
