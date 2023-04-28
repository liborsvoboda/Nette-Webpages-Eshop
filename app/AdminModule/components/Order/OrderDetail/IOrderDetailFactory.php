<?php


namespace App\AdminModule\Components\Order;


interface IOrderDetailFactory
{

    public function create($orderId): OrderDetail;

}
