<?php

namespace App\AdminModule\Components\Producer;

use App\Model\Factory\GridFactory;
use App\Model\Producer\ProducerRepository;
use Nette\Application\UI\Control;


class ProducerGrid extends Control
{
    private $producerRepository, $gridFactory;

    public $onEdit = [];

    public function __construct(ProducerRepository $producerRepository, GridFactory $gridFactory)
    {
        $this->producerRepository = $producerRepository;
        $this->gridFactory = $gridFactory;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('id', 'ID');
        $grid->addColumnText('name', 'Název');
        $grid->addAction('edit','', 'Edit', ['id'])
            ->setIcon('edit');
        
        $grid->addFilterText('name', 'Hledat', ['name']);
        return $grid;
    }

    private function getDataSource()
    {
        return $this->producerRepository->getAll();
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/producerGrid.latte');
    }
}