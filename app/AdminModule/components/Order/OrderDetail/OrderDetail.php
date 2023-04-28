<?php


namespace App\AdminModule\Components\Order;


use App\Components\Macros\PriceFilter;
use App\Model\Order\OrderRepository;
use App\Model\Shipping\ShippingRepository;
use Nette\Application\UI\Control;
use App\Model\Country\CountryRepository;
use App\Model\SuperFaktura\SuperFakturaService;
use App\Model\Fhb\FhbService;
use Nette\Database\Context;

class OrderDetail extends Control
{
    private $orderRepository, $orderId, $shippingRepository, $priceFilter, $countryRepository,$superFakturaService,$fhbService,$db;

    public $onMakeInvoice = [], $onDone = [], $onSendFhb = [];

    public function __construct($orderId, OrderRepository $orderRepository, ShippingRepository $shippingRepository, PriceFilter $priceFilter, CountryRepository $countryRepository, SuperFakturaService $superFakturaService, FhbService $fhbService, Context $db)
    {
        $this->orderRepository = $orderRepository;
        $this->orderId = $orderId;
        $this->shippingRepository = $shippingRepository;
        $this->priceFilter = $priceFilter;
        $this->countryRepository = $countryRepository;
        $this->superFakturaService = $superFakturaService;
        $this->fhbService = $fhbService;
        $this->db = $db;
    }

    public function render()
    {
        $order = $this->orderRepository->getById($this->orderId)->fetch();
        $this->template->otherCountryName = $this->countryRepository->getNameById($order->otherCountry);

        $items = $order->related('order_item')->fetchAll();
        $this->template->order = $order;
        $this->template->items = $items;
        $this->template->shippingPrice = $this->shippingRepository->getPriceById($order->shipping->id, $order->price);
        $this->template->orderStatuses = $this->orderRepository->getOrderStatuses(1)->fetchAll();
        $this->priceFilter->setCurrency($order->locale->currency->iso);
        $this->priceFilter->setLocale($order->locale->lang->locale);
        $this->template->render(__DIR__.'/templates/orderDetail.latte');
    }

    public function handleMakeProforma($orderId)
    {
        $this->onMakeInvoice($orderId, OrderRepository::INVOICE_PROFORMA);
    }

    public function handleMakeInvoice($orderId)
    {
        $this->onMakeInvoice($orderId, OrderRepository::INVOICE_REGULAR);
    }

    public function handleMakeStorno($orderId)
    {
        $this->onMakeInvoice($orderId, OrderRepository::INVOICE_STORNO);
    }

    public function handleSetPaid()
    {
        $order = $this->orderRepository->getById($this->orderId)->fetch();
        $this->orderRepository->setPaid($order->number);

    }

    public function handleSendIsklad($orderId)
    {
        //$this->onSendFhb($orderId);
        $newOrder = $this->orderRepository->getById($orderId)->fetch();
        if(!$newOrder) {
            return;
        }
        $newOrderItems = $this->orderRepository->getOrderItems($orderId)->fetchAll();
        $fhbId = $this->fhbService->sendOrder($newOrder, $newOrderItems);
        if($fhbId == true) {
            $this->db->table('orders')->where('id', $newOrder->id)->update(['fhbId' => $fhbId]);
            $this->fhbService->saveTrackingLink($orderId);
            $this->orderRepository->setStatus($orderId, OrderRepository::STATUS_SENT);
        }
    }

    public function handleSendSf($orderId)
    { 
        $newOrder = $this->orderRepository->getById($this->orderId)->fetch();
        $newOrderItems = $this->orderRepository->getOrderItems($orderId)->fetchAll();
        $sfId = $this->superFakturaService->sendNewOrder($newOrder, $newOrderItems, $newOrder['locale_id']);
        $this->orderRepository->getById($this->orderId)->update(['sfId' => $sfId]);
    }

    public function handleDownloadSf($orderId,$sfId)
    {
        $newOrder = $this->orderRepository->getById($this->orderId)->fetch();

        $response = $this->superFakturaService->getInvoice($sfId,($newOrder['locale_id'] == 1 ? 'slo' : 'cze'));
        header("Content-type:application/pdf");
        $filename = "Faktura_$orderId.pdf";
        header("Content-Disposition:attachment;filename=$filename");
        echo $response->body;
        exit();
    }

}