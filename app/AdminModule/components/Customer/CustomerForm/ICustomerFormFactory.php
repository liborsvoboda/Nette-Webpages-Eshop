<?php


namespace App\AdminModule\Components\Customer;


interface ICustomerFormFactory
{

    public function create(): CustomerForm;

}
