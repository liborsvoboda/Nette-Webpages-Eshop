<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Producer\IProducerFormFactory;
use App\AdminModule\Components\Producer\IProducerGridFactory;
use App\Model\Producer\ProducerRepository;

class ProducerPresenter extends CataloguePresenter
{
    /**
     * @var IProducerGridFactory
     * @inject
     */
    public $producerGrid;

    /**
     * @var IProducerFormFactory
     * @inject
     */
    public $producerForm;

    public $producerId;

    public function createComponentProducerGrid()
    {
        $grid = $this->producerGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        return $grid;
    }

    public function actionEdit($id)
    {
        $this->producerId = $id;
    }

    public function createComponentProducerAddForm()
    {
        $form = $this->producerForm->create();
        $form->onDone[] = function ($id) {
            $this->redirect('edit', $id);
        };
        return $form;
    }

    public function createComponentProducerEditForm()
    {
        $form = $this->producerForm->create();
        $form->setEdit($this->producerId);
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }


}