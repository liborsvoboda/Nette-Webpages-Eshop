<?php


namespace App\AdminModule\Components\Order;


interface IOrderGridFactory
{

    public function create(): OrderGrid;

}
