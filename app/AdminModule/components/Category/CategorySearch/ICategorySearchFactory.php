<?php

namespace App\FrontModule\Components\Category;

interface ICategorySearchFactory
{

    public function create(string $search): CategorySearch;

}
