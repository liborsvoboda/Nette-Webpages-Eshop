<?php


namespace App\FrontModule\Components\Product;


use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\TplSettingsService;
use App\Model\Services\ImageService;
use Nette\Application\UI\Control;
use App\Model\Setting\SettingRepository;


class ProductBox extends Control{

    private $productId;
    private $appSettingsService;
    private $tplSettingsService;
    private $productRepository, $imageService;
    private $allowCart = true;
    private $settingRepository;

    public function __construct($productId, 
    AppSettingsService $appSettingsService, 
    TplSettingsService $tplSettingsService, 
    ProductRepository $productRepository, 
    ImageService $imageService, 
    SettingRepository $settingRepository)
    {
        $this->productId = $productId;
        $this->appSettingsService = $appSettingsService;
        $this->tplSettingsService = $tplSettingsService;
        $this->productRepository = $productRepository;
        $this->imageService = $imageService;
        $this->settingRepository = $settingRepository;
    }

    public function setAllowCart(bool $allowCart): self
    {
        $this->allowCart = $allowCart;
        return $this;
    }

    public function render()
    {
        $this->template->addFunction('imgSize', function (...$args) {
            return $this->imageService->getImage($args);
        });

        $this->template->freeDelivery = $this->settingRepository->getFreeDelivery('SK');
        $this->template->allowCart = $this->allowCart;
        $this->template->tplSetting = $this->tplSettingsService;
        $this->template->product = $this->productRepository->getById($this->productId)->fetch();
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/Product/ProductBox/productBox.latte');
    }
}