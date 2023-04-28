<?php

namespace App\AdminModule\Components\Payment;

use App\Model\Factory\GridFactory;
use App\Model\Payment\PaymentRepository;
use Nette\Application\UI\Control;
use Nette\Utils\Html;

class PaymentGrid extends Control
{

    private $paymentRepository, $gridFactory;

    public $onEdit = [];

    public function __construct(PaymentRepository $paymentRepository, GridFactory $gridFactory)
    {
        $this->paymentRepository = $paymentRepository;
        $this->gridFactory = $gridFactory;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('name', 'Název');
        $grid->addColumnText('price', 'Cena');
        $grid->addColumnText('locale', 'Země')
            ->setRenderer(function ($row){
                return $row->locale->country->name;
            });
        $grid->addColumnText('enabled', 'Stav')
            ->setAlign('center')
            ->setRenderer([$this, 'showActive']);
        $grid->addAction('edit','', 'Edit', ['id'])
            ->setIcon('edit');
        return $grid;
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function showActive($data)
    {
        $out = new Html();
        if($data->enabled) {
            return $out->addHtml('<span class="text-success"><i class="fas fa-check"></i></span>');
        }
        return $out->addHtml('<span class="text-danger"><i class="fas fa-times"></i></span>');
    }


    private function getDataSource()
    {
        return $this->paymentRepository->getAll();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/paymentGrid.latte');
    }

}