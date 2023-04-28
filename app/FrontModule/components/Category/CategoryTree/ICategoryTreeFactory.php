<?php

namespace App\FrontModule\Components\Category;

interface ICategoryTreeFactory
{
    public function create(int $categoryId = null): CategoryTree;
}
