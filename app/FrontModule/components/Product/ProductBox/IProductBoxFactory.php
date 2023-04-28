<?php


namespace App\FrontModule\Components\Product;


interface IProductBoxFactory
{

    public function create($productId): ProductBox;

}
