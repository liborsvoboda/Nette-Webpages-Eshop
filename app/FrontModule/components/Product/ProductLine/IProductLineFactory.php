<?php


namespace App\FrontModule\Components\Product;


interface IProductLineFactory
{

    public function create($products): ProductLine;

}
