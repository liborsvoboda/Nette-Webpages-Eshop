<?php


namespace App\AdminModule\Components\Customer;


interface ICustomerGridFactory
{

    public function create(): CustomerGrid;

}
