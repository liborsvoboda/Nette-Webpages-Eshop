<?php


namespace App\AdminModule\Components\Payment;


interface IPaymentGridFactory
{

    public function create(): PaymentGrid;

}
