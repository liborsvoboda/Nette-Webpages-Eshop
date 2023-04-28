<?php

namespace App\AdminModule\Components\Order;

use App\Model\Factory\FormFactory;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;
use App\Model\Order\OrderRepository;
use App\Model\Order\Order;
use App\Model\Country\CountryRepository;
use App\Model\Shipping\ShippingRepository;
use App\Model\Payment\PaymentRepository;
use App\Model\Product\ProductRepository;

class OrderForm extends Control
{

    private $formFactory,
        $orderRepository,
        $countryRepository,
        $shippingRepository,
        $paymentRepository,
        $productRepository,
        $order;
    public $orderId = null,
        $onDone = [];

    public function __construct(
        FormFactory $formFactory,
        OrderRepository $orderRepository,
        CountryRepository $countryRepository,
        ShippingRepository $shippingRepository,
        PaymentRepository $paymentRepository,
        ProductRepository $productRepository
    )
    {
        $this->formFactory = $formFactory;
        $this->orderRepository = $orderRepository;
        $this->countryRepository = $countryRepository;
        $this->shippingRepository = $shippingRepository;
        $this->paymentRepository = $paymentRepository;
        $this->productRepository = $productRepository;
    }

    public function setEdit($orderId)
    {
        $this->orderId = $orderId;
        $this->order = $this->orderRepository->getById($this->orderId)->fetch();
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->orderRepository->update($values, $this->orderId);
        $this->onDone();
    }

    public function addProduct(Form $form)
    {
        $values = $form->getValues();
        $product = $this->productRepository->getById($values->product_id)->fetch();
        $this->orderRepository->saveOrderItemFromProduct($this->orderId, $product, $values->count);
        $newOrder = new Order($this->order);
        $newTotalPrice = $newOrder->recalculateTotalPrice();
        $this->orderRepository->updatePrice($this->order, $newTotalPrice);
        $this->onDone();

    }

    public function handleDeleteItem($itemId)
    {
        $this->orderRepository->deleteOrderItem($itemId);
        $order = $this->orderRepository->getById($this->orderId)->fetch();
        $newOrder = new Order($order);
        $newTotalPrice = $newOrder->recalculateTotalPrice();
        $this->orderRepository->updatePrice($this->orderId, $newTotalPrice);
        $this->onDone();
    }

    public function createComponentAddForm()
    {
        $form = $this->formFactory->create();
        $form->addSelect('product_id', 'form.item', $this->productRepository->getForSelect())
            ->setRequired('form.valid.FILLED');
        $form->addText('count', 'form.count')
            ->setRequired('form.valid.FILLED');
        $form->addSubmit('submit', 'form.add');
        $form->onSuccess[] = [$this, 'addProduct'];
        return $form;
    }


    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addText('firstName', 'form.firstName');
        $form->addText('companyName', 'form.companyName');
        $form->addText('lastName', 'form.lastName');
        $form->addText('email', 'form.email');
        $form->addText('street', 'form.street');
        $form->addText('city', 'form.city');
        $form->addText('zip', 'form.zip');
        $form->addText('ico', 'form.ico');
        $form->addText('dic', 'form.dic');
        $form->addText('icdph', 'form.icdph');
        $form->addSelect('country_id', 'form.country', $this->countryRepository->getForSelect());
        $form->addSelect('shipping_id', 'form.shipping', $this->shippingRepository->getForSelect());
        $form->addSelect('payment_id', 'form.payment', $this->paymentRepository->getForSelect());
        $form->addText('phone', 'form.phone');
        $form->addTextArea('note', 'form.note');
        $form->addText('otherName', 'form.otherName');
        $form->addText('otherSurname', 'form.otherSurname');
        $form->addText('otherStreet', 'form.otherStreet');
        $form->addText('otherCity', 'form.otherCity');
        $form->addText('otherZip', 'form.otherZip');
        $form->addSelect('otherCountry', 'form.otherCountry', $this->countryRepository->getForSelect());
        $form->addText('otherPhone', 'form.otherPhone');

        $form->setDefaults($this->order);
        $form->addSubmit('submit', 'form.save');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function render()
    {
        // $order = $this->orderRepository->getById($this->orderId)->fetch();
        $items = $this->order->related('order_item')->fetchAll();
        $this->template->items = $items;
        $this->template->order = $this->order;
        $this->template->render(__DIR__ . '/templates/orderForm.latte');
    }

}
