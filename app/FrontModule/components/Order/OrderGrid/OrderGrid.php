<?php

namespace App\FrontModule\Components\Order;

use App\Model\Factory\GridFactory;
use App\Model\Order\OrderRepository;
use Nette\Application\UI\Control;
use Nette\Utils\Html;

class OrderGrid extends Control
{
    private OrderRepository $orderRepository;
    private GridFactory $gridFactory;
    private $userId;
    private $paid = true;
    public $onDetail = [];

    public function __construct(OrderRepository $orderRepository, GridFactory $gridFactory)
    {
        $this->orderRepository = $orderRepository;
        $this->gridFactory = $gridFactory;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setPaid($paid)
    {
        $this->paid = $paid;
    }

    public function createComponentGrid()
    {
        $status = $this->orderRepository->getOrderStatuses(1);
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('number', 'Číslo')->setSortable()->setFilterText();
        $grid->addColumnDateTime('timestamp', 'Datum')->setFormat('d.m.Y G:i')->setAlign('left')->setSortable();
//        $grid->addColumnText('firstName', 'Jméno')->setSortable()->setFilterText();
//        $grid->addColumnText('lastName', 'Příjmení')->setSortable()->setFilterText();
        $grid->addColumnText('email', 'Email')->setSortable()->setFilterText();
        $grid->addColumnText('phone', 'Telefon')->setSortable()->setFilterText();
        $grid->addColumnText('price', 'Cena')->setSortable()
            ->setRenderer(function ($row) {
                return $row->price . ' ' . $row->locale->currency->symbol;
            });
        $grid->addColumnText('order_status_id','Stav')
            ->setAlign('center')
            ->setRenderer(function ($row) use ($status) {
                $out = new Html();
                $html = '<span class="badge badge-'.$status[$row->order_status_id]['color'].'">';
                $html .= $status[$row->order_status_id]['name'];
                $html .= '</span>';
                $out->addHtml($html);
                return $out;
            });
        $grid->addAction('detail', 'Detail', 'detail', ['id'])->setClass('');
        $grid->addFilterDateRange('timestamp', 'Datum');
        return $grid;
    }

    private function getDataSource()
    {
        if($this->paid) {
            return $this->orderRepository->getPaidOrders($this->userId);
        } else {
            return $this->orderRepository->getUnpaidOrders($this->userId);
        }
    }

    public function handleDetail($id)
    {
        $this->onDetail($id);
    }

    public function render()
    {
        $this->template->render(__DIR__.'/orderGrid.latte');
    }
}