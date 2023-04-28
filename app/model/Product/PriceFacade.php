<?php

namespace App\Model\Product;

use App\Model\Category\CategoryRepository;
use App\Model\LocaleRepository;
use App\Model\User\UserRepository;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Http\Session;
use Nette\Security\User;
use Nette\Utils\Strings;

class PriceFacade
{
    private $productRepository, $categoryRepository, $userRepository, $user, $currencyId, $localeRepository, $userLevelRepository, $session;

    public function __construct(ProductRepository $productRepository,
                                CategoryRepository $categoryRepository,
                                UserRepository $userRepository,
                                User $user,
                                UserLevelRepository $userLevelRepository,
                                Session $session,
                                LocaleRepository $localeRepository)
    {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->userRepository = $userRepository;
        $this->localeRepository = $localeRepository;
        $this->userLevelRepository = $userLevelRepository;
        $this->session = $session;
        $this->user = $user;
    }

    public function getUserPrice($productId, $currencyId = null, $defaultPrice = false, $userId = null)
    {
        $currencyId = $currencyId ?? $this->localeRepository->getCurrencyId();
        $localeId = $localeId ?? $this->localeRepository->getLocaleIdByCurrencyId($currencyId);
        $product = $this->productRepository->getById($productId)->fetch();
        if (!$this->user->isLoggedIn() || $defaultPrice === true) {
            $price = $product->related('product_price')->where('locale_id', $currencyId)->fetch();
            return $price ? $price->price : null;
        }
        $price = $product->related('product_price')->where('locale_id', $localeId)->fetch()->price;
        $categoryDiscount = isset($product->category->discounts) ? json_decode($product->category->discounts, true) : [];
        $aUser = $this->userRepository->getById($this->user->id)->fetch();
        if($aUser->user_level_id >= 1) {
            if(isset($orderData['tmp_user_level_id']) && $orderData['tmp_user_level_id'] > $aUser->user_level_id) {
                $user_group_id = $this->userLevelRepository->getGroupIdByLevel($orderData['tmp_user_level_id']);
            } else {
                $user_group_id = $aUser->user_level->user_group_id ?? null;
            }
            $price = $product->related('product_price')
                ->where('locale_id', $localeId)
                ->where('user_group_id', $user_group_id)
                ->fetch()->price;
        }
        if (isset($aUser->user_level->discount)) {
            $price = (100 - $aUser->user_level->discount) / 100 * $price;
        }
        if (!isset($aUser->user_level_id)) {
            return $price;
        }
        $discountPrice = $price;
        if (isset($categoryDiscount[$aUser->user_level->id])) {
            $discountPrice = (100 - (float)$categoryDiscount[$aUser->user_level->id]) / 100 * $price;
        }
        if (isset($productDiscount[$aUser->user_level->id])) {
            if($aUser->user_level_id > 1) {
                $price = $product->related('product_price')->where('currency_id', $currencyId)->fetch()->base_price;
            }
            if (strpos($productDiscount[$localeId][$aUser->user_level->id], '%') !== false) {
                $discount = str_ireplace('%', '', $productDiscount[$aUser->user_level->id]);
                $discountPrice = (100 - (float)$discount) / 100 * $price;
            } else {
                $vat = $price * ($this->getVat($productId)/100);
                $discountPrice = ($price + $vat - (float)$productDiscount[$localeId][$aUser->user_level->id]) - $vat;
            }
        }
        return $discountPrice;
    }

    public function getUserPriceVat($productId, $currencyId = null, $defaultPrice = false, $userId = null)
    {
        $currencyId = $currencyId ?? $this->localeRepository->getCurrencyId();
        $localeId = $localeId ?? $this->localeRepository->getLocaleIdByCurrencyId($currencyId);
        $product = $this->productRepository->getById($productId)->fetch();
        if (!$this->user->isLoggedIn() || $defaultPrice === true) {
            $price = $product->related('product_price')->where('locale_id', $currencyId)->fetch();
            return $price ? $price->price_vat : null;
        }
        $price = $product->related('product_price')->where('locale_id', $localeId)->fetch()->price_vat;
        $categoryDiscount = isset($product->category->discounts) ? json_decode($product->category->discounts, true) : [];
        $aUser = $this->userRepository->getById($this->user->id)->fetch();
        if($aUser->country_id == 2 && strlen($aUser->dic) > 0 && Strings::startsWith($aUser->dic, 'CZ') && $currencyId == 2) {
            return $this->getUserPrice($productId, $currencyId, $defaultPrice, $userId);
        }
        if($aUser->country_id > 2 && strlen($aUser->icdph) > 0 && $currencyId == 1) {
            return $this->getUserPrice($productId, $currencyId, $defaultPrice, $userId);
        }
        if($aUser->user_level_id >= 1) {
            $orderData = $this->session->getSection('orderData');
            if(isset($orderData['tmp_user_level_id']) && $orderData['tmp_user_level_id'] > $aUser->user_level_id) {
                $user_group_id = $this->userLevelRepository->getGroupIdByLevel($orderData['tmp_user_level_id']);
            } else {
                $user_group_id = $aUser->user_level->user_group_id ?? null;
            }
            $price = $product->related('product_price')
                ->where('locale_id', $localeId)
                ->where('user_group_id', $user_group_id)
                ->fetch()->price_vat;

        }
        if (isset($aUser->user_level->discount)) {
            $price = (100 - $aUser->user_level->discount) / 100 * $price;
        }
        if (!isset($aUser->user_level_id)) {
            return $price;
        }
        $discountPrice = $price;
        if (isset($categoryDiscount[$aUser->user_level->id])) {
            $discountPrice = (100 - (float)$categoryDiscount[$aUser->user_level->id]) / 100 * $price;
        }
        $productDiscount = isset($product->discounts) ? json_decode($product->discounts, true) : [];
        if (isset($productDiscount[$localeId][$aUser->user_level->id])) {
            if($aUser->user_level_id >= 1) {
                $price = $product->related('product_price')->where('currency_id', $currencyId)->fetch()->base_price_vat;
            }
            if (strpos($productDiscount[$localeId][$aUser->user_level->id], '%') !== false) {
                $discount = str_ireplace('%', '', $productDiscount[$aUser->user_level->id]);
                $discountPrice = (100 - (float)$discount) / 100 * $price;
            } else {
                $discountPrice = $price - (float)$productDiscount[$localeId][$aUser->user_level->id];
            }
        }
        return $discountPrice;
    }

    public function getOrigPrice($productId, $curencyId = null)
    {
        $currencyId = $curencyId ?? $this->localeRepository->getCurrencyId();
        $localeId = $localeId ?? $this->localeRepository->getLocaleIdByCurrencyId($currencyId);
        $product = $this->productRepository->getById($productId)->fetch();
        $price = $product->related('product_price')->where('currency_id', $currencyId)->fetch();
        return $price ? $price->orig_price : null;
    }

    public function getOrigPriceVat($productId, $curencyId = null)
    {
        $currencyId = $curencyId ?? $this->localeRepository->getCurrencyId();
        $localeId = $localeId ?? $this->localeRepository->getLocaleIdByCurrencyId($currencyId);
        $product = $this->productRepository->getById($productId)->fetch();
        $price = $product->related('product_price')->where('locale_id', $localeId)->fetch();
        return $price ? $price->orig_price_vat : null;
    }

    public function getVat($productId, $localeId)
    {
        if ($this->user->isLoggedIn()){
            $currencyId = $curencyId ?? $this->localeRepository->getCurrencyId();
            $product = $this->productRepository->getById($productId)->fetch();
            if(!$product) {
                return null;
            }
            $price = $product->related('product_price')->where('locale_id', $localeId)->fetch();
            $aUser = $this->userRepository->getById($this->user->id)->fetch();
            if($aUser->country_id == 2 && strlen($aUser->dic) > 0 && Strings::startsWith($aUser->dic, 'CZ') && $currencyId == 2) {
                return 0;
            }
            if($aUser->country_id > 2 && strlen($aUser->icdph) > 0 && $currencyId == 1) {
                return 0;
            }

            return $price ? $price->vat : null;
        } else {return null;}
    }

    public function getBasePrice($productId, $curencyId = null)
    {
        $currencyId = $curencyId ?? $this->localeRepository->getCurrencyId();
        $product = $this->productRepository->getById($productId)->fetch();
        $price = $product->related('product_price')->where('currency_id', $currencyId)->fetch();
        return $price ? $price->base_price : null;
    }

    public function getBasePriceVat($productId, $curencyId = null)
    {
        $currencyId = $curencyId ?? $this->localeRepository->getCurrencyId();
        $product = $this->productRepository->getById($productId)->fetch();
        $price = $product->related('product_price')->where('currency_id', $currencyId)->fetch();
        return $price ? $price->base_price_vat : null;
    }

    public function getDeliveryPrice($productId, $curencyId = null)
    {
        $currencyId = $curencyId ?? $this->localeRepository->getCurrencyId();
        $product = $this->productRepository->getById($productId)->fetch();
        $price = $product->related('product_price')->where('currency_id', $currencyId)->fetch();
        return $price ? $price->delivery_price : null;
    }

    public function getPurchasePrice($productId, $curencyId = null)
    {
        $currencyId = $curencyId ?? $this->localeRepository->getCurrencyId();
        $product = $this->productRepository->getById($productId)->fetch();
        $price = $product->related('product_price')->where('currency_id', $currencyId)->fetch();
        return $price ? $price->purchase_price : null;
    }

    public function getPriceMargin($productId, $currencyId = null)
    {
        $mo = $this->getUserPrice($productId, $currencyId);
        $vo = $this->getBasePrice($productId, $currencyId);
        
        $margin = (($mo - $vo) / $mo) * 100;
        return round($margin, 2);
    }

    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;
    }

    public function getNextUserPrice($productId, $currencyId = null, $defaultPrice = false, $userId = null)
    {
        $currencyId = $currencyId ?? $this->localeRepository->getCurrencyId();
        $localeId = $localeId ?? $this->localeRepository->getLocaleIdByCurrencyId($currencyId);
        $product = $this->productRepository->getById($productId)->fetch();
        if (!$this->user->isLoggedIn() || $defaultPrice === true) {
            $price = $product->related('product_price')->where('locale_id', $currencyId)->fetch();
            return $price ? $price->price : null;
        }
        $price = $product->related('product_price')->where('locale_id', $localeId)->fetch()->price;
        $categoryDiscount = isset($product->category->discounts) ? json_decode($product->category->discounts, true) : [];
        $aUser = $this->userRepository->getById($this->user->id)->fetch();
        if($aUser->user_level_id >= 1) {
            if(isset($orderData['tmp_user_level_id']) && $orderData['tmp_user_level_id'] > $aUser->user_level_id) {
                $user_group_id = $this->userLevelRepository->getGroupIdByLevel($orderData['tmp_user_level_id']);
            } else {
                $user_group_id = $aUser->user_level->user_group_id ?? null;
            }
            $price = $product->related('product_price')
                ->where('locale_id', $localeId)
                ->where('user_group_id', $user_group_id)
                ->fetch()->price;
        }
        if (isset($aUser->user_level->discount)) {
            $price = (100 - $aUser->user_level->discount) / 100 * $price;
        }
        if (!isset($aUser->user_level_id)) {
            return $price;
        }
        $discountPrice = $price;
        if (isset($categoryDiscount[$aUser->user_level->id])) {
            $discountPrice = (100 - (float)$categoryDiscount[$aUser->user_level->id]) / 100 * $price;
        }
        if (isset($productDiscount[$aUser->user_level->id])) {
            if($aUser->user_level_id >= 1) {
                $price = $product->related('product_price')->where('currency_id', $currencyId)->fetch()->base_price;
            }
            if (strpos($productDiscount[$localeId][$aUser->user_level->id], '%') !== false) {
                $discount = str_ireplace('%', '', $productDiscount[$aUser->user_level->id]);
                $discountPrice = (100 - (float)$discount) / 100 * $price;
            } else {
                $vat = $price * ($this->getVat($productId)/100);
                $discountPrice = ($price + $vat - (float)$productDiscount[$localeId][$aUser->user_level->id]) - $vat;
            }
        }
        return $discountPrice;
    }

    public function getNextUserPriceVat($productId, $currencyId = null, $defaultPrice = false, $userId = null)
    {
        $currencyId = $currencyId ?? $this->localeRepository->getCurrencyId();
        $localeId = $localeId ?? $this->localeRepository->getLocaleIdByCurrencyId($currencyId);
        $product = $this->productRepository->getById($productId)->fetch();
        if (!$this->user->isLoggedIn() || $defaultPrice === true) {
            $price = $product->related('product_price')->where('locale_id', $currencyId)->fetch();
            return $price ? $price->price_vat : null;
        }
        $price = $product->related('product_price')->where('locale_id', $localeId)->fetch()->price_vat;
        $categoryDiscount = isset($product->category->discounts) ? json_decode($product->category->discounts, true) : [];
        $aUser = $this->userRepository->getById($this->user->id)->fetch();
        if($aUser->user_level_id >= 1) {
            $orderData = $this->session->getSection('orderData');
/*            if(isset($orderData['tmp_user_level_id']) && $orderData['tmp_user_level_id'] > $aUser->user_level_id) {
                $user_group_id = $this->userLevelRepository->getGroupIdByLevel($orderData['tmp_user_level_id']);
            } else {
*/                $user_group_id = $aUser->user_level->user_group_id ?? null;
//            }
            $user_group_id = $user_group_id < 5 ? $user_group_id + 1 : 5;
            $price = $product->related('product_price')
                ->where('locale_id', $localeId)
                ->where('user_group_id', $user_group_id)
                ->fetch()->price_vat;

        }
        if (isset($aUser->user_level->discount)) {
            $price = (100 - $aUser->user_level->discount) / 100 * $price;
        }
        if (!isset($aUser->user_level_id)) {
            return $price;
        }
        $discountPrice = $price;
        if (isset($categoryDiscount[$aUser->user_level->id])) {
            $discountPrice = (100 - (float)$categoryDiscount[$aUser->user_level->id]) / 100 * $price;
        }
        $productDiscount = isset($product->discounts) ? json_decode($product->discounts, true) : [];
        if (isset($productDiscount[$localeId][$aUser->user_level->id])) {
            if($aUser->user_level_id >= 1) {
                $price = $product->related('product_price')->where('currency_id', $currencyId)->fetch()->base_price_vat;
            }
            if (strpos($productDiscount[$localeId][$aUser->user_level->id], '%') !== false) {
                $discount = str_ireplace('%', '', $productDiscount[$aUser->user_level->id]);
                $discountPrice = (100 - (float)$discount) / 100 * $price;
            } else {
                $discountPrice = $price - (float)$productDiscount[$localeId][$aUser->user_level->id];
            }
        }
        return $discountPrice;
    }

}