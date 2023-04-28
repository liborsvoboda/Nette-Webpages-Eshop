<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\Order\IOrderDetailFactory;
use App\AdminModule\Components\Order\IOrderGridFactory;
use App\AdminModule\Components\Order\IOrderFormFactory;
use App\Model\Fhb\FhbService;
use App\Model\Order\OrderRepository;
use App\Model\Order\Order;

class OrderPresenter extends OverviewPresenter {

    /**
     * @var IOrderGridFactory
     * @inject
     */
    public $orderGrid;

    /**
     * @var IOrderFormFactory
     * @inject
     */
    public $orderForm;

    /**
     * @var IOrderDetailFactory
     * @inject
     */
    public $orderDetail;

    /**
     * @var OrderRepository
     * @inject
     */
    public $orderRepository;

    /**
     * @var FhbService
     * @inject
     */
    public $fhbService;

    protected $editId = null;
    protected $orderId;

    public function actionDetail($id) {
        $this->orderId = $id;
    }

    public function actionEdit($id) {
        $this->editId = $id;
    }

    public function createComponentOrderGrid() {
        $grid = $this->orderGrid->create();
        $grid->onDetail[] = function ($id) {
            $this->redirect('Order:detail', $id);
        };
        $grid->onEdit[] = function ($id) {
            $this->redirect('Order:edit', $id);
        };
        return $grid;
    }

    public function createComponentOrderCustomerGrid() {
        $grid = $this->orderGrid->create();
        $grid->setOnlyUnregistered(true);
        $grid->onDetail[] = function ($id) {
            $this->redirect('Order:detail', $id);
        };
        $grid->onEdit[] = function ($id) {
            $this->redirect('Order:edit', $id);
        };
        return $grid;
    }

    public function createComponentOrderEditForm() {
        $form = $this->orderForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }

    public function createComponentOrderDetail() {
        $detail = $this->orderDetail->create($this->orderId);
        $detail->onMakeInvoice[] = function ($orderId, $type) {
            $this->redirect('makeInvoice', ['orderId' => $orderId, 'type' => $type]);
        };
        $detail->onMakeInvoice[] = function ($orderId) {
            $this->redirect('makeProforma', $orderId);
        };
        $detail->onSendFhb[] = function ($orderId) {
            $order = $this->orderRepository->getById($orderId)->fetch();
            $items = $this->orderRepository->getOrderItems($orderId)->fetchAll();
            $fhbId = $this->fhbService->sendOrder($order, $items);
            $order->update(['fhbId' => $fhbId]);
            $this->redirect('this');
        };
        $detail->onDone[] = function () {
            $this->redirect('this');
        };
        return $detail;
    }

    public function actionMakeInvoice($orderId, $type) {
        $response = $this->orderRepository->getInvoice($orderId, $type);
        if ($type == OrderRepository::INVOICE_REGULAR) {
            $this->orderRepository->setStatus($orderId, OrderRepository::STATUS_INVOICE);
        }
        if ($type == OrderRepository::INVOICE_STORNO) {
            $this->orderRepository->setStatus($orderId, OrderRepository::STATUS_DOBROPIS);
        }

        if (!$response) {
            echo 'Nastala neočekávaná chyba';
            die;
        }
        $this->sendResponse($response);
    }

}
