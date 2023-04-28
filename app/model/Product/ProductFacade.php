<?php

namespace App\Model\Product;

use App\Model\LocaleRepository;
use App\Model\ProductGallery\ProductGalleryRepository;
use Nette\Application\LinkGenerator;
use App\Model\Attribute\AttributeRepository;

class ProductFacade
{

    private $currencyId = 1;
    private $langId = 1;

    private $productRepository;
    private $productGalleryRepository;
    private $linkGenerator;
    private $attributeRepository;
    private $priceFacade;
    private $localeRepository;

    public function __construct(ProductRepository $productRepository,
                                ProductGalleryRepository $productGalleryRepository,
                                LinkGenerator $linkGenerator,
                                AttributeRepository $attributeRepository,
                                PriceFacade $priceFacade,
                                LocaleRepository $localeRepository)
    {
        $this->productRepository = $productRepository;
        $this->productGalleryRepository = $productGalleryRepository;
        $this->linkGenerator = $linkGenerator;
        $this->attributeRepository = $attributeRepository;
        $this->priceFacade = $priceFacade;
        $this->localeRepository = $localeRepository;
    }

    public function getAllJson($locale)
    {
        $this->setLocale($locale);
        $products = $this->productRepository->getAll($this->langId)->order('product.id');
        $productsArray = [];
        foreach ($products as $product) {
            $productsArray[] = $this->getArrayProduct($product);
        }
        return $productsArray;
    }

    public function getDetailJson($locale, $id)
    {
        $this->setLocale($locale);
        $product = $this->productRepository->getById($id)->fetch();
        $productsArray = [];
        if ($product) {
            $productsArray = $this->getArrayProduct($product);
        }

        return $productsArray;
    }

    public function getArrayProduct($product)
    {
        $images = $this->productGalleryRepository->getAllForProduct($product->id);
        $attributes = $this->attributeRepository->getProductAttributesAsArray($product->id);

        $imagesArray = [];
        $baseUrl = substr($this->linkGenerator->link('Front:Homepage:default'), 0, -1);
        if ($product->image) {
            $imagesArray[] = [
                'image' => $baseUrl . $product->image,
                'ord' => 1
            ];
        }
        foreach ($images as $image) {
            $imagesArray[] = [
                'image' => $baseUrl . $image->image,
                'ord' => $image->ord
            ];
        }
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'ean' => $product->ean,
            'price' => $this->priceFacade->getUserPrice($product->id, $this->currencyId, true),
            'price_with_vat' => $this->priceFacade->getUserPriceVat($product->id, $this->currencyId, true),
            'vat' => $this->priceFacade->getVat($product->id, $this->currencyId),
            'images' => $imagesArray,
            'attributes' => $attributes
        ];
    }

    private function setLocale($locale)
    {
        $loc = $this->localeRepository->getLocaleByCountryCode($locale)->fetch();
        if($loc) {
            $this->langId = $loc->lang_id;
            $this->currencyId = $loc->currency_id;
        }
    }

}
