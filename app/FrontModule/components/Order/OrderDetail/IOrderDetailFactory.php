<?php


namespace App\FrontModule\Components\Order;


interface IOrderDetailFactory
{

    public function create($orderId): OrderDetail;

}
