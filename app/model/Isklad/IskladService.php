<?php


namespace App\Model\Isklad;


use App\Model\LocaleRepository;
use App\Model\Product\PriceFacade;
use App\Model\Product\ProductRepository;
use Tracy\Debugger;


class IskladService
{
    private $auth_id = '5896318_bad',
        $auth_key = '96157658960202_bad',
        $auth_token = '1803be232a477_bad',
        $productRepository,
        $localeRepository,
        $priceFacade;

    private $shopSettingId = [
        'sk' => 1,
        'cs' => 1
    ];

    private $country = [
        'sk' => 'SK',
        'cs' => 'CZ'
    ];

    private $taxes = [
        'sk' => 20,
        'cs' => 15
    ];

    private $delivery = [
        'sk' => 7,
        'cs' => 8
    ];

    private $payment = [
        8 => 8,
        10 => 8
    ];

    public function __construct(ProductRepository $productRepository, PriceFacade $priceFacade, LocaleRepository $localeRepository)
    {
        $this->productRepository = $productRepository;
        $this->priceFacade = $priceFacade;
        $this->localeRepository = $localeRepository;
    }

    private function addItem($item, $amount, $price, $currencyId, $isCompany)
    {
        $tax = $this->priceFacade->getVat($item->id, $currencyId);
        if ($isCompany == true) {
            $tax = 0;
        }
        $out = [
            'item_id' => $item['lang_sku'],
            'catalog_id' => $item['lang_sku'],
            'name' => $item['name'],
            'count' => $amount,
            'expiration' => 0,
            'exp_value' => '',
            'price' => round($price / ((100 + $tax) / 100), 2),
            'price_with_tax' => $price,
            'tax' => $tax,
        ];
        return $out;
    }

    public function sendNewOrder($order, $items, $localeId)
    {
        $locale = $this->localeRepository->getLocaleByLocaleId($localeId);
        $curencyId = $this->localeRepository->getCurrencyIdByLocaleId($localeId);
        $curency = $this->localeRepository->getCurrencyIsoByLocaleId($localeId);
        $langId = $this->localeRepository->getLangIdByLocaleId($localeId);
        $article = [];
        $baseTax = $this->taxes[$locale];
        $isCompany = false;

        if ($locale == 'cs' && $order['isCompany'] == 1) {
            $isCompany = true;
            $baseTax = 0;
        }
        foreach ($items as $item) {
            $product = $this->productRepository->getById($item->product_id, $langId)->fetch();
            $article[] = $this->addItem($product, $item->count, $item->price, $curencyId, $isCompany);
        }
        if( isset($order->isVoucher) ){
            $discountVat = $order->isVoucher ? $order->discount : 0;
            $discount = $order->isVoucher ? round($order->discount / $baseTax, 2) : 0;
        }
        $data = [
            'original_order_id' => (string)$order->id,
            'reference_number' => $order->number,
            'customer_name' => $order['firstName'],
            'customer_surname' => $order['lastName'],
            'customer_phone' => $order['phone'],
            'customer_email' => $order['email'],
            'name' => strlen($order['otherName']) > 1 ? $order['otherName'] : $order['firstName'],
            'surname' => strlen($order['otherSurname']) > 1 ? $order['otherSurname'] : $order['lastName'],
            'phone' => $order['phone'],
            'email' => $order['email'],
            'company' => isset($order['companyName']) ? $order['companyName'] : '',
            'street' => strlen($order['otherStreet']) > 1 ? $order['otherStreet'] : $order['street'],
            'street_number' => '',
            'entrance_number' => '',
            'door_number' => '',
            'postal_code' => strlen($order['otherZip']) > 1 ? $order['otherZip'] : $order['zip'],
            'city' => strlen($order['otherCity']) > 1 ? $order['otherCity'] : $order['city'],
            'country' => $this->country[$locale],
            'gps_lat' => '',
            'gps_long' => '',
            'fa_company' => isset($order['companyName']) ? $order['companyName'] : '',
            'fa_street' => $order['street'],
            'fa_street_number' => '',
            'fa_postal_code' => $order['zip'],
            'fa_city' => $order['city'],
            'fa_country' => $this->country[$locale],
            'fa_ico' => strlen($order['ico']) > 1 ? $order['ico'] : '',
            'fa_dic' => strlen($order['dic']) > 1 ? $order['dic'] : '',
            'fa_icpdh' => strlen($order['icdph']) > 1 ? $order['icdph'] : '',
            'invoice_url' => '',
            'note' => '',
            'currency' => $curency,
            'default_tax' => $baseTax,
            'payment_card' => $order->payment_id == 4 ? 4 : 0,
            'payment_cod' => $this->payment[$order->payment_id] == 8 ? 1 : 0,
            'cod_price_without_tax' => $this->payment[$order->payment_id] == 8 ? $order['price'] / ((100 + $baseTax) / 100) : 0,
            'cod_price' => $this->payment[$order->payment_id] == 8 ? $order['price'] : 0,
            'deposit_without_tax' => 0,
            'deposit' => 0,
            'shop_setting_id' => $this->shopSettingId[$locale] ?? 0,
            'destination_country_code' => $this->country[$locale],
            'id_delivery' => $this->delivery[$locale],
            'delivery_branch_id' => 0,
            'external_branch_id' => 0,
            'delivery_price_without_tax' => $order->shipping->price / ((100 + $baseTax) / 100),
            'delivery_price' => $order->shipping->price,
            'id_payment' => $this->payment[$order->payment_id],
            'payment_price_without_tax' => $this->payment[$order->payment_id] == 8 ? $order->payment->price / ((100 + $baseTax) / 100) : 0,
            'payment_price' => $this->payment[$order->payment_id] == 8 ? $order->payment->price : 0,
            'discount_price_without_tax' => $discountVat ?? 0,
            'discount_price' => $discountVat ?? 0,
            'items' => $article,
        ];
        Debugger::log(json_encode($data));
        \IskladRestApi::Initialize($this->auth_id, $this->auth_key, $this->auth_token);
        \IskladRestApi::CreateNewOrder($data);
        $response = \IskladRestApi::GetResponse();
        Debugger::log(serialize($response));
        return $response['decodedResponse']['response']['resp_data']['order_id'];
    }

    public function getOrderStatus($orderId)
    {
        \IskladRestApi::Initialize($this->auth_id, $this->auth_key, $this->auth_token);
        \IskladRestApi::GetOrderStatus(['original_order_id' => $orderId]);
        $response = \IskladRestApi::GetResponse();
        dump($response);
        die;
    }

    public function exportProduct($data)
    {
        \IskladRestApi::Initialize($this->auth_id, $this->auth_key, $this->auth_token);
        \IskladRestApi::UpdateInventoryCard($data);
    }

    public function exportProducts()
    {
        $products = $this->productRepository->getAllAdmin()->where('isCombo', false);
        foreach ($products as $values) {
            $data = [
                'item_id' => $values['id'],
                'parent_catalog_id' => '',
                'sku' => $values['sku'],
                'catalog_id' => '',
                'name' => $values['name'],
                'mj' => $values['unit'],
                'ean' => $values['ean'],
                'enabled' => 1,
                'category' => [],
                'producer' => $values['producer']->name,
                'price_without_tax' => $values['price'],
                'old_price_without_tax' => (float)$values['origPrice'],
                'tax' => $values['vat'],
                'supplier' => '',
                'supplier_other' => [],
                'short_description' => '',
                'long_description' => $values['description'],
                'min_order_count' => '1',
                'tech_char' => [],
                'tech_param' => [],
                'images' => []
            ];
            $this->exportProduct($data);
        }
    }
}