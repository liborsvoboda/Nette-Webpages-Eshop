<?php


namespace App\FrontModule\Components\Cart;


use App\Model\Cart\CartRepository;
use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;
use Nette\Security\User;

class CartRow extends Control
{
    private $productId,
        $appSettingsService,
        $productRepository,
        $cartRepository,
        $userRepository,
        $user;
    public $onDone = [], $summary = false;

    public function __construct($productId, AppSettingsService $appSettingsService, ProductRepository $productRepository, CartRepository $cartRepository, User $user, UserRepository $userRepository)
    {
        $this->productId = $productId;
        $this->appSettingsService = $appSettingsService;
        $this->productRepository = $productRepository;
        $this->cartRepository = $cartRepository;
        $this->userRepository = $userRepository;
        $this->user = $user;
    }

    public function setSummary()
    {
        $this->summary = true;
    }

    public function render()
    {
        if ($this->user->isLoggedIn()) {
            $user = $this->userRepository->getById($this->user->getId())->fetch();
            $userLevel = $user->user_level_id ?? null;
            if (!$userLevel) {
                $userLevel = 'default';
            }
        } else {
            $userLevel = 'default';
        }

        $limits = $this->productRepository->getLimits($this->productId, $userLevel);

        $this->template->isSummary = $this->summary;
        $this->template->product = $this->productRepository->getById($this->productId)->fetch();
        $this->template->row = $this->cartRepository->getItem($this->productId);
        $this->template->min = $limits->min > 0 ? $limits->min : 1;
        $this->template->max = $limits->max;
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir() . '/Cart/CartRow/cartRow.latte');
    }

    public function handlecartRemove($id)
    {
        $this->cartRepository->removeFromCart($id);
        $this->onDone();
    }
}
