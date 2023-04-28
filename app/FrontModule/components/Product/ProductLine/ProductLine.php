<?php


namespace App\FrontModule\Components\Product;


use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Multiplier;

class ProductLine extends Control
{
    private $productBox, $products, $appSettingsService;
    private $allowCart = true;

    public function __construct($products, IProductBoxFactory $productBox, AppSettingsService $appSettingsService)
    {
        $this->products = $products;
        $this->productBox = $productBox;
        $this->appSettingsService = $appSettingsService;
    }
    
    public function setAllowCart(bool $allowCart): self
    {
        $this->allowCart = $allowCart;
        return $this;
    }

    public function createComponentProductBox()
    {
        return new Multiplier(function ($product){
            $productBox = $this->productBox->create($product);
            $productBox->setAllowCart($this->allowCart);
            return $productBox;
        });
    }

    public function render()
    {
        $this->template->products = $this->products;
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/Product/ProductLine/productLine.latte');
    }

}