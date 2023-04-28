<?php


namespace App\FrontModule\Components\Cart;


interface ICartRowFactory
{

    public function create($productId): CartRow;

}
