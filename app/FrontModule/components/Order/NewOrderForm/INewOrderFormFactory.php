<?php


namespace App\FrontModule\Components\Order;


interface INewOrderFormFactory
{

    public function create(): NewOrderForm;

}
