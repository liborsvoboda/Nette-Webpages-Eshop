<?php


namespace App\FrontModule\Components\Category;


interface ICategoryFilterFactory
{

    public function create($section, $parent = null): CategoryFilter;

}
