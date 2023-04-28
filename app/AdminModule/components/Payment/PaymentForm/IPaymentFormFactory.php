<?php


namespace App\AdminModule\Components\Payment;


interface IPaymentFormFactory
{

    public function create(): PaymentForm;

}
