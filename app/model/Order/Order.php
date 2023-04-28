<?php

namespace App\Model\Order;

use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nette\Database\Table\ActiveRow;

class Order
{
    use SmartObject;

    /** @var ActiveRow */
    private $order;

    /** @var array */
    private $itemPrices;

    /**
     * @param ActiveRow $order
     */
    public function __construct(ActiveRow $order)
    {
    	$this->order = $order;
    }

    /**
     * @param bool $withVat
     * @return float
     */
    public function getTotal(bool $withVat = false): float
    {
    	if ($withVat) return $this->order->price;
    	return $this->order->price - $this->getTotalVat();
    }

    /**
     * @return float
     */
    public function getTotalVat(): float
    {
    	$vat = $this->getItemsPriceVat() + $this->getShippingPriceVat() + $this->getPaymentPriceVat();
    	return $vat;
    }
    
    public function recalculateTotalPrice(){
        $price = $this->getItemsPrice(true) + $this->getShippingPrice(true) + $this->getPaymentPrice(true);
    	return $price;
    }

    /**
     * @param bool $withVat
     * @return float
     */
    public function getItemsPrice(bool $withVat = false): float
    {
    	$items = $this->getItems();
    	$price = 0;
    	foreach ($items as $item) {
    		$price += $item['total'];
    	}
    	if ($withVat) $price += $this->getItemsPriceVat();
    	return $price;
    }
    
    /**
     * @return float
     */
    public function getItemsPriceVat(): float
    {
    	$items = $this->getItems();
    	$vat = 0;
    	foreach ($items as $item) {
    		$vat += $item['vat_total'];
    	}
    	return $vat;
    }

    /**
     * @param bool $withVat
     * @return float
     */
    public function getShippingPrice(bool $withVat = false): float
    {
    	$shipping = $this->order->shipping;
    	$price = $shipping->price;
    	if (!$withVat) $price -= $this->getShippingPriceVat();
    	return $price;
    }

    /**
     * @return float
     */
    public function getShippingPriceVat(): float
    {
    	$shipping = $this->order->shipping;
    	return $this->getVat($shipping->price, $shipping->vat);
    }

    /**
     * @param bool $withVat
     * @return float
     */
    public function getPaymentPrice(bool $withVat = false): float
    {
    	$payment = $this->order->payment;
    	$price = $payment->price;
    	if (!$withVat) $price -= $this->getPaymentPriceVat();
    	return $price;
    }

    /**
     * @return float
     */
    public function getPaymentPriceVat(): float
    {
    	$payment = $this->order->payment;
    	return $this->getVat($payment->price, $payment->vat);
    }

    /**
     * @return ActiveRow
     */
    public function getOrder(): ActiveRow
    {
    	return $this->order;
    }

    /**
     * @return array|ArrayHash[]
     */
    public function getItems(): array
    {
    	if ($this->itemPrices === null) {
    		$this->itemPrices = [];
            $items = $this->order->related('order_item');
            $localeId = $this->order->locale_id;
    		foreach ($items as $item) {
                $userGroupId = 1;
                if(isset($this->order->user_id) && $this->order->user_id != null) {
                    $userGroupId = $this->order->user->user_group_id;
                }
                $priceData = $item->product->related('product_price')
                    ->where('product_price.locale_id', $localeId)
                    ->where('user_group_id', $userGroupId)
                    ->fetch();
    			$vatPercent = $priceData->vat;
    			$total = $item->price * $item->count;
    			$this->itemPrices[] = ArrayHash::from([
    				'item' => $item,
    				'product' => $item->product,
    				'price_unit' => $item->price - $this->getVat($item->price, $vatPercent),
    				'price_unit_with_vat' => $item->price,
    				'vat_unit' => $this->getVat($item->price, $vatPercent),
    				'total' => $total - $this->getVat($total, $vatPercent),
    				'total_with_vat' => $total,
    				'vat_total' => $this->getVat($total, $vatPercent)
    			]);
    		}
    	}
    	return $this->itemPrices;
    }

    /**
     * @param float $priceWithVat
     * @param float $vatPercent
     * @return float
     */
    private function getVat(float $priceWithVat, float $vatPercent): float
    {
    	$coef = (100 + $vatPercent) / 100;
    	return ($priceWithVat / $coef) * ($vatPercent / 100);
    }
}