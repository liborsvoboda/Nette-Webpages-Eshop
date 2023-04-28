<?php


namespace App\FrontModule\Components\Cart;


interface ICartConfirmFactory
{

    public function create(): CartConfirm;

}
