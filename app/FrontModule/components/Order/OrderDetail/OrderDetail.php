<?php


namespace App\FrontModule\Components\Order;


use App\Components\Macros\PriceFilter;
use App\Model\Order\OrderRepository;
use App\Model\Shipping\ShippingRepository;
use Nette\Application\UI\Control;
use App\Model\Country\CountryRepository;
use App\Model\SuperFaktura\SuperFakturaService;

class OrderDetail extends Control
{
    private $orderRepository, $orderId, $shippingRepository, $priceFilter, $countryRepository,$superFakturaService;

    public $onDone = [];

    public function __construct($orderId, OrderRepository $orderRepository, ShippingRepository $shippingRepository, PriceFilter $priceFilter, CountryRepository $countryRepository, SuperFakturaService $superFakturaService)
    {
        $this->orderRepository = $orderRepository;
        $this->orderId = $orderId;
        $this->shippingRepository = $shippingRepository;
        $this->priceFilter = $priceFilter;
        $this->countryRepository = $countryRepository;
        $this->superFakturaService = $superFakturaService;
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

    public function handleMakeCopy($orderId)
    {
        //$this->redirect('Account:newOrder', $orderId);
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
        $this->onSendFhb($orderId);
    }

    public function handleSendSf($orderId)
    {

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