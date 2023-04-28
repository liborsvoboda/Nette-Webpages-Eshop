<?php


namespace App\FrontModule\Components\Cart;


interface ICartTotalFactory
{

    public function create(): CartTotal;

}
