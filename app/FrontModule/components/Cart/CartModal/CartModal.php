<?php


namespace App\FrontModule\Components\Cart;


use App\Model\Cart\CartRepository;
use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;

class CartModal extends Control
{
    private $productId = null,
        $amount,
        $appSettingsService,
        $productRepository,
        $cartRepository;
    public $onDone = [];

    public function __construct(AppSettingsService $appSettingsService, ProductRepository $productRepository, CartRepository $cartRepository)
    {
        $this->appSettingsService = $appSettingsService;
        $this->productRepository = $productRepository;
        $this->cartRepository = $cartRepository;
    }

    public function setItem($productId, $amount)
    {
        $this->productId = $productId;
        $this->amount = $amount;
    }

    public function render()
    {
        if ($this->productId) {
            $this->template->product = $this->productRepository->getById($this->productId)->fetch();
            $this->template->row = $this->cartRepository->getItem($this->productId);
            $this->template->amount = $this->amount;
        }
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir() . '/Cart/CartModal/cartModal.latte');
    }
}