<?php


namespace App\Model\Cart;


use App\Model\BaseRepository;
use App\Model\Commission\MonthlyCommissionRepository;
use App\Model\LocaleRepository;
use App\Model\Product\BaseVatEnum;
use App\Model\Product\PriceFacade;
use App\Model\Product\ProductRepository;
use App\Model\User\UserRepository;
use App\Model\UserLevel\UserLevelRepository;
use App\Model\Voucher\VoucherRepository;
use Nette\Http\Session;
use Nette\Security\User;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

class CartRepository extends BaseRepository
{
    private $session, $section, $user, $productRepository, $priceFacade, $userRepository, $voucherRepository, $localeRepository,
        $monthlyCommissionRepository, $userLevelRepository;

    public function __construct(Session $session,
                                User $user,
                                ProductRepository $productRepository,
                                PriceFacade $priceFacade,
                                UserRepository $userRepository,
                                VoucherRepository $voucherRepository,
                                MonthlyCommissionRepository $monthlyCommissionRepository,
                                UserLevelRepository $userLevelRepository,
                                LocaleRepository $localeRepository)
    {
        $this->user = $user;
        $this->productRepository = $productRepository;
        $this->session = $session;
        $this->section = $session->getSection('cart');
        $this->priceFacade = $priceFacade;
        $this->userRepository = $userRepository;
        $this->voucherRepository = $voucherRepository;
        $this->localeRepository = $localeRepository;
        $this->monthlyCommissionRepository = $monthlyCommissionRepository;
        $this->userLevelRepository = $userLevelRepository;
    }

    public function addToCart($productId, $amount)
    {
        $partnerProductCount = $this->getPartnerProductsCount();
        $partnerGratisProductCount = $this->getGratisPartnerProductsCount();

        if ($this->user->isLoggedIn()) {
            $user = $this->userRepository->getById($this->user->getId())->fetch();
            $userLevel = $user->user_level_id ?? null;
            if (!$userLevel) {
                $userLevel = 'default';
            }
        } else {
            $userLevel = 'default';
        }

        $limits = $this->productRepository->getLimits($productId, $userLevel);
        $product = $this->productRepository->getById($productId)->fetch();

        $freeProduct = 0;
        if ($this->user->isLoggedIn() && ($partnerProductCount != 6 && $partnerProductCount != 12 && $partnerProductCount !=24 && $partnerGratisProductCount == 0)){
            $freeProduct = (
                  ($partnerProductCount >=6 && $partnerProductCount < 12 && $partnerGratisProductCount == 0 && $product->commission && $product->comProductAmount == 1)
               || ($partnerProductCount >=12 && $partnerProductCount < 24 && $partnerGratisProductCount < 2 && $product->commission && $product->comProductAmount == 1)
               || ($partnerProductCount >=24 && $partnerGratisProductCount < 4 && $product->commission && $product->comProductAmount == 1)
            )
            ? 1 : 0;
        }

        if (isset($this->section->items[(string)$productId])) {
            $currentAmount = $this->section->items[(string)$productId]['amount'];
            if ($limits->max > 0 && (($currentAmount + $amount) > $limits->max)) {
                throw new ProductLimitException('products.limit.max', $limits->max);
            }
            
            $this->section->items[(string)$productId]['amount'] += $amount;
            if ($freeProduct && $this->section->items[(string)$productId]['amount'] > (float)$this->section->items[(string)$productId]['freeAmount']) {
                $this->section->items[(string)$productId]['freeAmount'] += 1;
            }
            if ($this->section->items[(string)$productId]['amount'] < (float)$this->section->items[(string)$productId]['freeAmount']){
                $this->section->items[(string)$productId]['freeAmount'] = $this->section->items[(string)$productId]['amount'];
            }
        } else {
            if ($limits->min > 0 && $amount < $limits->min) {
                throw new ProductLimitException('products.limit.min', $limits->min);
            }
            if ($limits->max > 0 && $amount > $limits->max) {
                throw new ProductLimitException('products.limit.max', $limits->max);
            }

            $price = $this->priceFacade->getUserPriceVat($productId);
            $this->section->items[(string)$productId] = [
                'id' => $productId,
                'amount' =>(float)$amount,
                'price' => $price,
                'vat' => $this->priceFacade->getVat($productId, $this->localeId()),
                'name' => $product->name,
                'sku' => $product->sku,
                'ean' => $product->ean,
                'image' => $product->image,
                'slug' => $product->slug,
                'weight'=> $product->weight,
                'width'=> $product->width,
                'commissions' => $product->commissions,
                'isCommissioned' => $product->commission,
                'comProductAmount' => $product->comProductAmount,
                'freeAmount' => $freeProduct ? ((float)$amount >= 1) ? 1 : 0 : 0
            ];
        }
    }

    public function removeFromCart($productId)
    {
        if (isset($this->section->items[$productId])) {
            unset($this->section->items[$productId]);
        }
    }

    public function getProductsCount()
    {
        $count = 0;
        if (isset($this->section->items)) {
            foreach ($this->section->items as $item) {
                $count += $item['amount'];
            }
        }
        $this->removeGratisItems();

        return $count;
    }

    public function removeGratisItems(){
        //remove gratis item from cart
        $partnerProductCount = $this->getPartnerProductsCount();
        $partnerGratisProductCount = $this->getGratisPartnerProductsCount();

        if (
               ($partnerProductCount < 6 && $partnerGratisProductCount > 0)
            || ($partnerProductCount < 12 && $partnerGratisProductCount > 1)
            || ($partnerProductCount < 24 && $partnerGratisProductCount > 2)
            || !$this->user->isLoggedIn()
        ){
            foreach ($this->section->items as $item) {
                if ($item['freeAmount'] > 0) {$this->section->items[$item['id']]['freeAmount'] = 0;}
                if ($item['price'] == 0) {unset($this->section->items[$item['id']]);}
            }
        }

    }

    public function getPartnerProductsCount()
    {
        $count = 0;
        if (isset($this->section->items)) {
            foreach ($this->section->items as $item) {
              if ($item['isCommissioned'] && $item['price'] != 0) {  $count += ($item['amount'] * $item['comProductAmount']) - $item['freeAmount'];}
            }
        }
        return $count;
    }

    public function getGratisPartnerProductsCount()
    {
        $count = 0;
        if (isset($this->section->items)) {
            foreach ($this->section->items as $item) {
              if ($item['isCommissioned'] && $item['freeAmount'] > 0) {  $count += $item['freeAmount'];}
            }
        }

        return $count;
    }

    public function recalculateCartWhenSignIn()
    {
        $changed = false;
        if (isset($this->section->items)) {
            $this->checkSpecialLevelChange();
            $changed = $this->checkLevelChange();
            foreach ($this->section->items as $item) {
                $product = $this->productRepository->getById($item['id'])->fetch();
                $this->section->items[$item['id']]['price'] = ($this->section->items[$item['id']]['price'] == 0) ? 0 :  $this->priceFacade->getUserPriceVat($product->productId);
                $this->section->items[$item['id']]['vat'] = ($this->section->items[$item['id']]['price'] == 0) ? 0 : $this->priceFacade->getVat($product->productId, $this->localeId());
            }
        }
        return $changed;
    }

    public function recalculateCartWhenSignOut()
    {
        $this->removeGratisItems();

        if (isset($this->section->items)) {
            $this->checkLevelChange();
            foreach ($this->section->items as $item) {
                $product = $this->productRepository->getById($item['id'])->fetch();
                $this->section->items[$item['id']]['price'] = $this->priceFacade->getUserPriceVat($product->productId);
                $this->section->items[$item['id']]['vat'] = $this->priceFacade->getVat($product->productId, $this->localeId());
            }
        }
    }

    public function checkLevelChange()
    {   

        if ($this->session->getSection('voucher')) {
            $voucherCode = $this->session->getSection('voucher')->voucher;
            if (isset($voucherCode['code'])){
                $this->voucherRepository->saveVoucher($voucherCode['code']);
            }
        }

        if ($this->user->isLoggedIn()){
            $userId = $this->user->getId();
            $orderData = $this->session->getSection('orderData');
            $auser = $this->userRepository->getById($userId)->fetch();
            //$currencyId = $currencyId ?? $this->localeRepository->getCurrencyId();
            /*if($auser->user_level_id < 3) {
                return false;
            }*/
            $now = new DateTime();
            $monthlyTurnover = $this->monthlyCommissionRepository->sumComGroupTurnover($userId, $now->format('m'), $now->format('Y'), $this->langId());
            $nextLevel = $auser->user_level->user_group_id < 5 ? $auser->user_level->user_group_id + 1: $auser->user_level->user_group_id;
            if($this->langId() == 2) {
                $minTurnover = $this->userLevelRepository->getAll()->where('user_group_id', $nextLevel)->order('min_turnover_cz')->fetch()->min_turnover_cz;
            } else {
                $minTurnover = $this->userLevelRepository->getAll()->where('user_group_id', $nextLevel)->order('min_turnover')->fetch()->min_turnover;
            }
            $nextCartPrice = $this->getItemsNextPrice();
            $nextTurnover = ($nextCartPrice);
            if($nextTurnover >= $minTurnover) {
                if($this->langId() == 2) {
                    $tmpLevel = $this->userLevelRepository->getAll()->where('min_turnover_cz <= ?', $nextTurnover)->order('min_turnover_cz DESC')->fetch();
                } else {
                    $tmpLevel = $this->userLevelRepository->getAll()->where('min_turnover <= ?', $nextTurnover)->order('min_turnover DESC')->fetch();
                }

                if($orderData['tmp_user_level_id'] == $tmpLevel->user_group_id) {
                    return false;
                } else {
                    $orderData['tmp_user_level_id'] = $tmpLevel->user_group_id;
                    return true;
                }
            } else {
                if ($orderData['tmp_user_level_id'] == $auser->user_level->user_group_id) {
                    return false;
                } else {
                    $orderData['tmp_user_level_id'] = $auser->user_level->user_group_id;
                    return true;
                }
            }
        } else {return false;}
    }

    public function getItemsNextPrice($withVat = true)
    {
        $price = 0;
        $isCZCompany = false;
        $orderData = $this->session->getSection('orderData');
        $locale = isset($orderData['locale_id']) ? $this->localeRepository->getLocaleByLocaleId($orderData['locale_id']) : '';
        $company = isset($orderData['isCompany']) && $orderData['isCompany'] == 1 ? true : false;
        if ($locale == 'cs' && $company == true) {
            $isCZCompany = true;
        }
        if (isset($this->section->items)) {
            foreach ($this->section->items as $item) {
                if ($withVat === true) {
                    $price += $this->priceFacade->getNextUserPriceVat($item['id']) * ($item['amount'] - $item['freeAmount']);
                } else {
                    $price += $isCZCompany ? ($item['amount'] - $item['freeAmount']) * $item['price'] : round(($item['amount'] - $item['freeAmount']) * $item['price'] / ((100 + $item['vat']) / 100), 2);
                }
            }
        }
        return $price;
    }

    public function checkSpecialLevelChange()
    {  if ($this->user->isLoggedIn()){
            $userId = $this->user->getId();
            $auser = $this->userRepository->getById($userId)->fetch();
            $orderData = $this->session->getSection('orderData');
            if($auser->user_level_id == 1) {
                $cartCount = $this->getCarCount();
                if($cartCount >= 6) {
                    $orderData['tmp_user_level_id'] = 3;
                } else {
                    unset($orderData['tmp_user_level_id']);
                }
            }
        }
    }

    public function getCarCount()
    {
        $cartCount = 0;
        $cartItems = $this->getItems();
        foreach ($cartItems as $item) {
            $product = $this->productRepository->getById($item['id'])->fetch();
            $isCombo = $product->related('product_combo')->fetchAll();
            if($isCombo) {
                foreach ($isCombo as $comboItem) {
                    $cartCount += $comboItem->amount * $item['amount'];
                }
            } else {
                $cartCount += $item['amount'];
            }
        }
        return $cartCount;
    }

    public function getReachedLevel()
    {
        $userId = $this->user->getId();
        $auser = $this->userRepository->getById($userId)->fetch();
        $orderData = $this->session->getSection('orderData');
        $level = $orderData['tmp_user_level_id'] ?? $auser->user_level->user_group_id;
        $userGroup = $this->userLevelRepository->getGroupById($level)->fetch();
        return $userGroup->name;
    }

    public function getReachedLevelId()
    {
        $userId = $this->user->getId();
        $auser = $this->userRepository->getById($userId)->fetch();
        $orderData = $this->session->getSection('orderData');
        $level = $orderData['tmp_user_level_id'] ?? $auser->user_level->user_group_id;
        $userGroup = $this->userLevelRepository->getGroupById($level)->fetch();
        return $userGroup->id;
    }

    public function clearCart()
    {
        if (isset($this->section)) {
            unset($this->section->items);
        }
        if ($this->session->getSection('voucher')) {
            unset($this->session->getSection('voucher')->voucher);
        }
    }

    public function getItemsDetail()
    {
        if (isset($this->section->items)) {
            foreach ($this->section->items as $key => $item) {
                $itemIds[] = $key;
            }
            $items = $this->productRepository->getById($itemIds);
            return $items;
        } else {
            return null;
        }
    }

    public function getItems()
    {
        if (isset($this->section->items)) {
            return $this->section->items;
        } else {
            return null;
        }
    }

    public function getItem($id)
    {
        if (isset($this->section->items[$id])) {
            return $this->section->items[$id];
        } else {
            return null;
        }
    }

    public function updateCartCount($id, $amount)
    {
        if ($this->section->items[$id]) {
            $this->section->items[$id]['amount'] = $amount;

            $this->removeGratisItems();

            $partnerProductCount = $this->getPartnerProductsCount();
            $partnerGratisProductCount = $this->getGratisPartnerProductsCount();

            $freeProduct = 0;
            if ($this->user->isLoggedIn()) {
                $freeProduct = (
                      ($partnerProductCount >=6 && $partnerProductCount < 12 && $partnerGratisProductCount == 0 && $this->section->items[$id]['isCommissioned'] && $this->section->items[$id]['comProductAmount'] == 1)
                   || ($partnerProductCount >=12 && $partnerProductCount < 24 && $partnerGratisProductCount < 2 && $this->section->items[$id]['isCommissioned'] && $this->section->items[$id]['comProductAmount'] == 1)
                   || ($partnerProductCount >=24 && $partnerGratisProductCount < 4 && $this->section->items[$id]['isCommissioned'] && $this->section->items[$id]['comProductAmount'] == 1)
                )
                ? 1 : 0;
                $this->section->items[$id]['freeAmount'] += $freeProduct ? ($this->section->items[$id]['amount'] < (float)$this->section->items[$id]['freeAmount']+1) ? $this->section->items[$id]['amount'] : 1 : 0;
                if ($this->section->items[$id]['amount'] < $this->section->items[$id]['freeAmount']) {$this->section->items[$id]['freeAmount'] = $this->section->items[$id]['amount'];}
            }
        }
    }

    public function getTotalPrice($withPaymentShipping = false, $withVat = true)
    {
        $price = $this->getItemsPrice($withVat);
        bdump($price);
        if($withPaymentShipping == true) {
            bdump($this->getShippingPrice($withVat));
            $price += $this->getShippingPrice($withVat);
            bdump($this->getPaymentPrice($withVat));
            $price += $this->getPaymentPrice($withVat);
        }
        return $price;
    }

    public function getTotalPriceWithDiscount($withPaymentShipping = false)
    {
        $price = $this->getTotalPrice($withPaymentShipping);
        $itemsComPrice = $this->getComItemsPrice(false);
        $discount = $this->voucherRepository->getDiscount($itemsComPrice);
        return $price - $discount;
    }

    public function getTotalPriceWOVat($discount = false, $withPaymentShipping = false)
    {
        $currencyId = $currencyId ?? $this->localeRepository->getCurrencyId();
        $aUser = $this->userRepository->getById($this->user->id)->fetch();
        if($aUser->country_id == 2 && strlen($aUser->dic) > 0 && Strings::startsWith($aUser->dic, 'CZ') && $currencyId == 2) {
            return $this->getTotalPrice($withPaymentShipping, true);
        }
        if($aUser->country_id > 2 && strlen($aUser->icdph) > 0 && $currencyId == 1) {
            return $this->getTotalPrice($withPaymentShipping, true);
        }
        $price = $this->getTotalPrice($withPaymentShipping, false);
        return $price;
    }

    public function getTotalVat($discount = false, $withPaymentShipping = false)
    {
        $price = $this->getTotalPrice($withPaymentShipping);
        $woVat = $price - $this->getTotalPriceWOVat($discount, $withPaymentShipping);
        //$itemsPrice = $this->getItemsPrice();
        //$discount = $this->voucherRepository->getDiscount($itemsPrice);
        return $woVat;
    }

    public function getItemsPrice($withVat = true)
    {
        $price = 0;
        $isCZCompany = false;
        $orderData = $this->session->getSection('orderData');
        $locale = isset($orderData['locale_id']) ? $this->localeRepository->getLocaleByLocaleId($orderData['locale_id']) : '';
        $company = isset($orderData['isCompany']) && $orderData['isCompany'] == 1 ? true : false;
        $company = false;
        if ($locale == 'cs' && $company == true) {
            $isCZCompany = true;
        }
        if (isset($this->section->items)) {
            foreach ($this->section->items as $item) {
                if ($withVat === true) {
                    $price += ($item['amount'] - $item['freeAmount']) * $item['price'];
                } else {
                    $price += $isCZCompany ? ($item['amount'] - $item['freeAmount']) * $item['price'] : round(($item['amount'] - $item['freeAmount']) * $item['price'] / ((100 + $item['vat']) / 100), 2);
                }
            }
        }
        return $price;
    }

    public function getComItemsPrice($withVat = true)
    {
        $price = 0;
        $isCZCompany = false;
        $orderData = $this->session->getSection('orderData');
        $locale = isset($orderData['locale_id']) ? $this->localeRepository->getLocaleByLocaleId($orderData['locale_id']) : '';
        $company = isset($orderData['isCompany']) && $orderData['isCompany'] == 1 ? true : false;
        $company = false;
        if ($locale == 'cs' && $company == true) {
            $isCZCompany = true;
        }
        if (isset($this->section->items)) {
            foreach ($this->section->items as $item) {
                if($item['isCommissioned']){
                    if ($withVat === true) {
                        $price += ($item['amount'] - $item['freeAmount']) * $item['price'];
                    } else {
                        $price += $isCZCompany ? ($item['amount'] - $item['freeAmount']) * $item['price'] : round(($item['amount'] - $item['freeAmount']) * $item['price'] / ((100 + $item['vat']) / 100), 2);
                    }
                }
            }
        }
        return $price;
    }

    public function getShippingPrice($withVat = true)
    {
        $isCZCompany = false;
        $orderData = $this->session->getSection('orderData');
        $localeId = $orderData['locale_id'] ?? $this->localeRepository->getLocaleByLangId($this->langId());
        $locale = $this->localeRepository->getLocaleByLocaleId($localeId);
        $company = isset($orderData['isCompany']) && $orderData['isCompany'] == 1 ? true : false;
        if ($locale == 'cs' && $company == true) {
            $isCZCompany = true;
        }
        if (isset($orderData->orderData['shipping'])) {
            if ($withVat === true) {
                return $orderData->orderData['shippingPrice'];
            } else {
                $vat = $isCZCompany ? 0 : BaseVatEnum::TAXES[$locale];
                return $orderData->orderData['shippingPrice'] / ((100 + $vat) / 100);
            }
        }
        return 0;
    }

    public function getPaymentPrice($withVat = true)
    {
        $isCZCompany = false;
        $orderData = $this->session->getSection('orderData');
        $localeId = $orderData['locale_id'] ?? $this->localeRepository->getLocaleByLangId($this->langId());
        $locale = $this->localeRepository->getLocaleByLocaleId($localeId);
        $company = isset($orderData['isCompany']) && $orderData['isCompany'] == 1 ? true : false;
        if ($locale == 'cs' && $company == true) {
            $isCZCompany = true;
        }
        if (isset($orderData->orderData['payment'])) {
            if ($withVat === true) {
                return $orderData->orderData['paymentPrice'];
            } else {
                $vat = $isCZCompany ? 0 : BaseVatEnum::TAXES[$locale];
                return $orderData->orderData['paymentPrice'] / ((100 + $vat) / 100);
            }
        }
        return 0;
    }

    public function calculateWeight()
    {
        $items = $this->getItems();
        $weight = 0;
        foreach($items as $item){
            $weight+= $item['weight'];
            
        }
        return $weight;
    }

    public function isActiveOrder()
    {
        return isset($this->section->items);
    }
}


/**
 * Class ProductLimitException
 * @package App\Model\Cart
 */
class ProductLimitException extends \Exception
{
}
