<?php


namespace App\FrontModule\Components\Product;


interface IProductFilterFactory
{

    public function create($section): ProductFilter;

}
