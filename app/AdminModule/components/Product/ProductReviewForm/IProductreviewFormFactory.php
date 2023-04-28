<?php
declare(strict_types=1);

namespace App\AdminModule\Components\Product;


/**
 * Interface IProductReviewFormFactory
 * @package App\FrontModule\Components\Product
 */
interface IProductReviewFormFactory
{
    public function create(): ProductReviewForm;
}
