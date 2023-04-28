<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Shipping\IShippingFormFactory;
use App\AdminModule\Components\Shipping\IShippingGridFactory;
use App\AdminModule\Components\Shipping\IShippingLevelGridFactory;

class ShippingPresenter extends SettingPresenter
{
    /**
     * @var IShippingGridFactory
     * @inject
     */
    public $shippingGrid;

    /**
     * @var IShippingFormFactory
     * @inject
     */
    public $shippingForm;

    /**
     * @var IShippingLevelGridFactory
     * @inject
     */
    public $shippingLevelGrid;

    public $editId = null, $shippingId = null;

    public function actionEdit($id)
    {
        $this->editId = $id;
    }

    public function actionEditLevels($shippingId)
    {
        if(!$shippingId) {
            $this->redirect('default');
        }
        $this->shippingId = $shippingId;
    }

    public function createComponentShippingGrid()
    {
        $grid = $this->shippingGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        $grid->onDone[] = function () {
            $this->redirect('this');
        };
        $grid->onLevelsEdit[] = function ($shippingId) {
            $this->redirect('EditLevels', $shippingId);
        };
        return $grid;
    }

    public function createComponentShippingForm()
    {
        $form = $this->shippingForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }

    public function createComponentShippingLevelGrid()
    {
        $grid = $this->shippingLevelGrid->create();
        $grid->setShippingId($this->shippingId);
        return $grid;
    }
}