<?php


namespace App\AdminModule\Components\BlogCategory;


interface IBlogCategoryGridFactory
{

    public function create(): BlogCategoryGrid;

}
