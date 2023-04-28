<?php


namespace App\FrontModule\Components\Cart;


use App\Model\Cart\CartRepository;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;

class CartCount extends Control
{
    private $cartRepository, $appSettingsService;

    public function __construct(CartRepository $cartRepository, AppSettingsService $appSettingsService)
    {
        $this->cartRepository = $cartRepository;
        $this->appSettingsService = $appSettingsService;
    }

    public function createComponentCartCount()
    {

    }

    public function render()
    {
        $this->template->count = $this->cartRepository->getProductsCount();
        $this->template->total = $this->cartRepository->getTotalPrice();
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/Cart/CartCount/cartCount.latte');
    }
}