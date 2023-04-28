<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Attribute\IAttributeFormFactory;
use App\AdminModule\Components\Attribute\IAttributeGridFactory;
use App\AdminModule\Components\Attribute\IAttributeValueGridFactory;

class ProductAttributePresenter extends CataloguePresenter
{
    /**
     * @var IAttributeGridFactory
     * @inject
     */
    public $attributeGrid;

    /**
     * @var IAttributeFormFactory
     * @inject
     */
    public $attributeForm;

    /**
     * @var IAttributeValueGridFactory
     * @inject
     */
    public $attributeValueGrid;

    private $editId = null, $attributeId;

    public function actionEdit($id)
    {
        $this->editId = $id;
    }

    public function actionValues($attributeId)
    {
        $this->attributeId = $attributeId;
    }

    public function createComponentAttributeGrid()
    {
        $grid = $this->attributeGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        $grid->onValuesEdit[] = function ($id) {
            $this->redirect('values', $id);
        };
        $grid->onDone[] = function () {
            $this->redirect('this');
        };
        return $grid;
    }

    public function createComponentAttributeForm()
    {
        $form = $this->attributeForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }

    public function createComponentAttributeValueGrid()
    {
        $grid = $this->attributeValueGrid->create($this->attributeId);
        return $grid;
    }
}