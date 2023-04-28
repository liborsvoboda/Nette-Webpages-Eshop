<?php


namespace App\FrontModule\Components\Product;


interface IProductSortingFormFactory
{

    public function create($section): ProductSortingForm;

}
