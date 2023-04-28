<?php

namespace App\AdminModule\Components\Slider;

use App\Model\Factory\GridFactory;
use App\Model\Slider\SliderRepository;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class SliderGrid extends Control
{
    private $sliderRepository, $gridFactory;

    public $onEdit = [];

    public function __construct(SliderRepository $sliderRepository, GridFactory $gridFactory)
    {
        $this->sliderRepository = $sliderRepository;
        $this->gridFactory = $gridFactory;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('id', 'Id');
        $grid->addColumnText('heading', 'Nadpis');
        $grid->addColumnText('lang', 'Jazyk')
            ->setRenderer(function($column) {
                return $column->lang->name;
            });
        $grid->addColumnText('button', 'Text');
        $grid->addAction('edit','', 'Edit', ['id'])
            ->setIcon('edit');
        $grid->addAction('remove', '')
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        return $grid;
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function handleRemove($id)
    {
        $this->sliderRepository->remove($id);
        $this['grid']->redraWControl();
    }


    private function getDataSource()
    {
        return $this->sliderRepository->getAll();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/sliderGrid.latte');
    }

}