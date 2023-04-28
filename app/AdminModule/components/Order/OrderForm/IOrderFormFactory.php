<?php


namespace App\AdminModule\Components\Order;


interface IOrderFormFactory
{

    public function create(): OrderForm;

}
