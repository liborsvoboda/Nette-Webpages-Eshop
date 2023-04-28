<?php

namespace App\AdminModule\Components\Order;

use App\Model\Factory\GridFactory;
use App\Model\Order\OrderRepository;
use Nette\Application\UI\Control;
use Nette\Utils\Html;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;
use App\Model\Payment\PaymentRepository;

class OrderGrid extends Control
{

    private $orderRepository,$paymentRepository, $gridFactory, $onlyUnregistered = false;

    public $onDetail = [], $onEdit = [], $allOrders = false;

    public function __construct(OrderRepository $orderRepository, GridFactory $gridFactory, PaymentRepository $paymentRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->gridFactory = $gridFactory;
        $this->paymentRepository = $paymentRepository;
    }

    public function setOnlyUnregistered($value)
    {
        $this->onlyUnregistered = $value;
    }

    public function createComponentGrid()
    {

        $status = $this->orderRepository->getOrderStatuses(1);
        $grid = new DataGrid();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('number', 'Číslo')->setSortable()->setFilterText();
        $grid->addColumnDateTime('timestamp', 'Datum')->setFormat('d.m.Y G:i')->setAlign('left')->setSortable();
        $grid->addColumnText('firstName', 'Jméno')->setSortable()->setFilterText();
        $grid->addColumnText('lastName', 'Příjmení')->setSortable()->setFilterText();
        $grid->addColumnText('email', 'Email')->setSortable()->setFilterText();
        $grid->addColumnText('phone', 'Telefon')->setSortable()->setFilterText();
        $grid->addColumnText('price', 'Cena')->setSortable();
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

        //function ($row) {$paymentName = $this->paymentRepository->getLangNameById($status[$row->payment_id]);};

        //$paymentName = $this->paymentRepository->getNameById($status[$row->payment_id]);
        $grid->addColumnText('payment_id', 'Typ Platby')
                ->setRenderer(function ($row){
                    return $this->paymentRepository->getNameById($row->payment_id);
                })->setSortable();
        $grid->addColumnStatus('isPaid', 'Platba')
            ->addOption(0, 'Nie')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(1, 'Áno')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changePaid'];
        $grid->setRememberState(false);
        $grid->addAction('edit', '', 'edit', ['id'])
            ->setRenderCondition(function ($row) {
                return $row->order_status_id == OrderRepository::STATUS_NEW;
            })
            ->setIcon('edit');
        $grid->addAction('cancel', '', 'cancel', ['id'])
            ->setRenderCondition(function ($row) {
                return $row->order_status_id == OrderRepository::STATUS_NEW;
            })
            ->setIcon('times')
            ->setConfirmation(
                new StringConfirmation('Stornovat?')
            );
        $grid->addAction('delete', '', 'delete', ['id'])
            ->setRenderCondition(function ($row) {
                return $row->order_status_id == OrderRepository::STATUS_STORNO;
            })
            ->setIcon('trash')
            ->setConfirmation(
                new StringConfirmation('Hodit od koše?')
            );
        $grid->addAction('detail', 'Detail', 'detail', ['id']);
        $grid->addFilterSelect('order_status_id', 'Stav', $this->orderStatusesPairs());
        $grid->addFilterDateRange('timestamp', 'Datum');
        $grid->setDefaultPerPage(20);
        return $grid;
    }

    public function orderStatusesPairs()
    {
        $statuses = $this->orderRepository->getOrderStatusesPairs();
        $out = ['' => 'Vše'];
        $out += $statuses;
        return $out;
    }

    public function updateStatus($id, $value)
    {
        $this->orderRepository->setStatus($id, $value, true);
        $this['grid']->redrawItem($id);
    }

    public function changePaid($id, $value)
    {
        $this->orderRepository->setPaidStatus($id, $value);
        $this['grid']->redrawItem($id);
    }


    public function handleDetail($id)
    {
        $this->onDetail($id);
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function handleDelete($id)
    {
        $this->orderRepository->setDumped($id);
    }

    public function handleCancel($id)
    {
        $this->orderRepository->setStatus($id, OrderRepository::STATUS_STORNO, true);
        $this['grid']->redrawControl();
    }

    private function getDataSource()
    {
        $orders = $this->orderRepository->getNonDumped()->order('id DESC');
        if($this->allOrders) {
            return $orders;
        }
        if($this->onlyUnregistered) {
            $orders->where('user_id', null);
        } else {
            $orders->where('user_id IS NOT NULL');
        }
        return $orders;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/orderGrid.latte');
    }

}
