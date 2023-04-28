<?php


namespace App\Model\LatteFunc;


use App\Model\Product\PriceFacade;
use App\Model\Product\ProductRepository;

class UserPrice
{

    private $priceFacade;

    public function __construct(PriceFacade $priceFacade)
    {
        $this->priceFacade = $priceFacade;
    }

    public function getPrice($productId)
    {
        return $this->priceFacade->getUserPrice($productId);
    }

    public function getPriceVat($productId)
    {
        return $this->priceFacade->getUserPriceVat($productId);
    }

    public function getBasePrice($productId)
    {
        return $this->priceFacade->getBasePrice($productId);
    }

    public function getBasePriceVat($productId)
    {
        return $this->priceFacade->getBasePriceVat($productId);
    }

    public function getPriceMargin($productId)
    {
        return $this->priceFacade->getPriceMargin($productId);
    }

    // přidáme následující metody
    public function hasOrigPrice($productId)
    {
        return ($this->getOrigPriceVat($productId) > 0);
    }

    public function getOrigPriceVat($productId)
    {
        return $this->priceFacade->getOrigPriceVat($productId);
    }

    public function getOrigPrice($productId)
    {
        return $this->priceFacade->getOrigPrice($productId);
    }

    public function getVat($productId)
    {
        return $this->priceFacade->getVat($productId, 1);
    }
}