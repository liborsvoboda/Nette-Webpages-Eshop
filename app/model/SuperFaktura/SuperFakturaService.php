<?php


namespace App\Model\SuperFaktura;


use App\Model\LocaleRepository;
use App\Model\Payment\PaymentRepository;
use App\Model\Product\PriceFacade;
use App\Model\Product\ProductRepository;
use App\Model\Shipping\ShippingRepository;
use Nette\Utils\Strings;
use Tracy\Debugger;
use App\Model\Country\CountryRepository;


class SuperFakturaService
{
    private $email = 'thomas.zsigmond@app.com',
        $apiToken = '8ce8109c6c661abd1afde36b1775b6df_bad',
        $companyId = '29200',
        $productRepository,
        $localeRepository,
        $paymentRepository,
        $priceFacade;

    private $sequences = [
        'sk' => 28999,
        'cs' => 36576
    ];

    private $paymentTypes = [
        PaymentRepository::COD => 'cod',
        PaymentRepository::CARD => 'debit',
        PaymentRepository::TRANSFER => 'transfer'
    ];


    public static $sfHash = 'hfhgwielbdvajghfgjkdadjadajdnbad';
    private ShippingRepository $shippingRepository;

    public function __construct(ProductRepository $productRepository,
                                PriceFacade $priceFacade,
                                LocaleRepository $localeRepository,
                                PaymentRepository $paymentRepository, ShippingRepository $shippingRepository, CountryRepository $countryRepository)
    {
        $this->productRepository = $productRepository;
        $this->priceFacade = $priceFacade;
        $this->localeRepository = $localeRepository;
        $this->paymentRepository = $paymentRepository;
        $this->shippingRepository = $shippingRepository;
        $this->countryRepository = $countryRepository;
    }

    public function sendNewOrder($order, $items, $localeId, $isProforma = false, $test = false)
    {

        $locale = $this->localeRepository->getLocaleByLocaleId($localeId);
        $curency = $this->localeRepository->getCurrencyIsoByLocaleId($localeId);
        $curencyId = $this->localeRepository->getCurrencyIdByLocaleId($localeId);
        $langId = $this->localeRepository->getLangIdByLocaleId($localeId);
        $orderNumber = $order->number;

        $api = new \SFAPIclient($this->email, $this->apiToken, '', 'API', $this->companyId);
        if (isset($order['isCompany']) && $order['isCompany']) {

            $client = array(
                'name' => isset($order['companyName']) ? $order['companyName'] : '',
                'ico' => isset($order['ico']) ? $order['ico'] : '',
                'dic' => isset($order['dic']) ? $order['dic'] : '',
                'ic_dph' => isset($order['icdph']) ? $order['icdph'] : '',
                'email' => isset($order['email']) ? $order['email'] : '',
                'address' => isset($order['street']) ? $order['street'] : '',
                'city' => isset($order['city']) ? $order['city'] : '',
                'zip' => isset($order['zip']) ? $order['zip'] : '',
                'phone' => isset($order['phone']) ? $order['phone'] : '',
                'country_iso_id' => isset($order->country->code) ? strtoupper($order->country->code) : null
            );

        } else {

            $nameS = $order['firstName'] . ' ' . $order['lastName'];
            $client = array(
                'name' => isset($order['firstName']) ? $nameS : '',
                'ico' => isset($order['ico']) ? $order['ico'] : '',
                'dic' => isset($order['dic']) ? $order['dic'] : '',
                'ic_dph' => isset($order['icdph']) ? $order['icdph'] : '',
                'email' => isset($order['email']) ? $order['email'] : '',
                'address' => isset($order['street']) ? $order['street'] : '',
                'city' => isset($order['city']) ? $order['city'] : '',
                'zip' => isset($order['zip']) ? $order['zip'] : '',
                'phone' => isset($order['phone']) ? $order['phone'] : '',
                'country_iso_id' => isset($order->country->code) ? strtoupper($order->country->code) : null
            );

        }

        if ($order["otherAddress"] == 1){
            $client["delivery_name"] = isset($order["otherName"]) ? ($order["otherName"] . ' ' . $order["otherSurname"]) : null;
            $client["delivery_address"] = isset($order["otherStreet"]) ? $order["otherStreet"] : null;
            $client["delivery_city"] = isset($order["otherCity"]) ? $order["otherCity"] : null;
            $client["delivery_zip"] = isset($order["otherZip"]) ? $order["otherZip"] : null;
            $client["delivery_country_iso_id"] = isset($order->otherCountry) ? strtoupper($this->countryRepository->getCodeById($order->otherCountry)) : null;
            $client["delivery_phone"] = isset($order["otherPhone"]) ? $order["otherPhone"] : null;
        }
        $api->setClient($client);

        $sequence = $this->sequences[$locale];
        if ($locale == 'cs' && $order['isCompany'] == 1) {
            $sequence = $this->sequences['cs'];
        }

        $invoice = array(
            'sequence_id' => $sequence,
            'variable' => $orderNumber,
            'type' => $isProforma ? 'proforma' : 'regular',
            'invoice_currency' => $curency,
            'rounding' => ($locale == 'cs') ? 'document' : 'item', 
			'issued_by' => 'app, s.r.o.',
			'issued_by_phone' => '+421 908 942 147',
			'issued_by_email' => 'info@app.com',
            'comment' => ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? '' : 'Dodanie tovaru je oslobodené od dane. Dodanie služby podlieha preneseniu daňovej povinnosti.' : '',
			'issued_by_web' => ($locale == 'cs') ? 'www.app.cz' : 'www.app.sk',
            'bank_accounts' =>($locale == 'cs') ? 
                [array(
                    'account' => '2801597408',
                    'bank_code' => '2010',
                    'bank_name' => 'Fio banka (CZK)',
                    'iban' => 'CZ1320100000002801597408',
                    'swift' => 'FIOBCZPPXXX', 
                    'show_account' => true
                )] : 
                [array(
                    'account' => '2301399325', 
                    'bank_code' => '8330', 
                    'bank_name' => 'Fio banka (EUR)', 
                    'iban' => 'SK4783300000002301399325', 
                    'swift' => 'FIOZSKBA', 
                    'show_account' => true
                )]
        );
        if($isProforma == true) {
            $invoice['invoice_no_formatted'] = $order->number;
        }

//        if(FaPayment::gi()->isCoD(orderData('payment'))) {
        $invoice['delivery_type'] = 'courier';
        $invoice['payment_type'] = $this->paymentTypes[$this->paymentRepository->getTypeById($order->payment_id)];
//        }

        /*if( isset($order->discount) ) {
            $invoice['discount_total'] = $order->discount;
        }*/

        $api->setInvoice($invoice);

        $itemsPrice = 0;
        $itemsPriceWithVat = 0;
        $totalPrice = 0;
        $freeAmount = 0;
        foreach ($items as $item) {
            $product = $this->productRepository->getById($item->product_id, $langId)->fetch();

            if ($order["otherAddress"] == 1){
                $vat = $this->priceFacade->getVat($product->id, $order->otherCountry);
            } else if(isset($order->country->id)){
                $vat = $this->priceFacade->getVat($product->id, $order->country->id);
            } else {
                $vat = $this->priceFacade->getVat($product->id, $curencyId);
            }
            
            $itemPrice = round(($item['price']) / (($vat + 100) / 100), 2);
            if (($item['count']-$item['freeAmount']) > 0){
                $api->addItem(
                    array(
                        'name' => $product->name,
                        'description' => '',
                        'quantity' => (int)($item['count']-$item['freeAmount']),
                        'unit' => 'ks',
                        'tax' => ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? $vat : 0 : $vat,
                        'unit_price' => $itemPrice
                    )
                );
                $itemsPrice +=  $itemPrice * ($item['count']-$item['freeAmount']);
                $itemsPriceWithVat += $item['price'] * ($item['count']-$item['freeAmount']);
            }

            if ($item['freeAmount'] > 0){
                $api->addItem(
                    array(
                        'name' => $product->name,
                        'description' => '',
                        'quantity' => (int)$item['freeAmount'],
                        'unit' => 'ks',
                        'tax' => ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? $vat : 0 : $vat,
                        'unit_price' => ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? round((1 / (($vat + 100) / 100)),2) : 1 : round((1 / (($vat + 100) / 100)),2)
                    )
                );
                $freeAmount += (int)$item['freeAmount'];
            }
        }
        $totalPrice += ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? $itemsPriceWithVat : $itemsPrice : $itemsPriceWithVat;

        if ($freeAmount > 0){
           $api->addItem(
                array(
                    'name' => ($locale == 'cs') ? 'Sleva ve smyslu dohody' : 'Zľava v zmysle dohody',
                    'description' => '',
                    'quantity' => 1,
                    'unit' => '',
                    'tax' => ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? $vat : 0 : $vat,
                    'unit_price' => -(($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? round(($freeAmount / (($vat + 100) / 100)),2) : $freeAmount : round(($freeAmount / (($vat + 100) / 100)),2))
                )
            );
        }

        if ((bool)$order['voucherCode']){
            $api->addItem(
                array(
                    'name' => ($locale == 'cs') ? 'Sleva ' . $order['voucherCode'] : 'Zľava ' . $order['voucherCode'] ,
                    'description' => '',
                    'quantity' => 1,
                    'unit' => '',
                    'tax' => ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? $vat : 0 : $vat,
                    'unit_price' => -(($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? round(($order["discount"]) / (($vat + 100) / 100), 2) : round($order["discount"], 2) : round(($order["discount"]) / (($vat + 100) / 100), 2))
                )
            );
            $totalPrice += -(($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? round(($order["discount"]) / (($vat + 100) / 100), 2) : round($order["discount"], 2) : round(($order["discount"]) / (($vat + 100) / 100), 2));
        }

        /*
                if( faSession( 'orderdeliveryInfoGift' ) )
                {
                    $api->addItem(
                        array(
                            'name' 			=> 'Darčekové balenie',
                            'description' 	=> '',
                            'quantity' => 1,
                            'unit' => 'ks',
                            'unit_price' => ( 5 ),
                            'tax' => 20
                        )
                    );
                }
        */
        $api->addItem(
            array(
                'name' => $order->shipping->name,
                'description' => '',
                'quantity' => 1,
                'unit' => 'ks',
                'tax' => ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? $vat : 0 : $vat,
                'unit_price' => ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? round($this->shippingRepository->getPrice($itemsPrice, $order->shipping_id) / (($vat + 100) / 100), 2) : round($this->shippingRepository->getPrice($itemsPrice, $order->shipping_id),2) : round($this->shippingRepository->getPrice($itemsPrice, $order->shipping_id) / (($vat + 100) / 100), 2)
            )
        );
        $totalPrice += round($this->shippingRepository->getPrice($itemsPrice, $order->shipping_id) / (($vat + 100) / 100), 2);

        $api->addItem(
            array(
                'name' => $order->payment->name,
                'description' => '',
                'quantity' => 1,
                'unit' => 'ks',
                'tax' => ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? $vat : 0 : $vat,
                'unit_price' => ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? round($this->paymentRepository->getPriceById($order->payment_id) / (($vat + 100) / 100), 2) : round($this->paymentRepository->getPriceById($order->payment_id),2) : round($this->paymentRepository->getPriceById($order->payment_id) / (($vat + 100) / 100), 2)
            )
        );
        $totalPrice += ($locale == 'cs' && isset($order['dic'])) ? (strlen($order['dic']) == 0 ) ? round($this->paymentRepository->getPriceById($order->payment_id) / (($vat + 100) / 100), 2) : round($this->paymentRepository->getPriceById($order->payment_id),2) : round($this->paymentRepository->getPriceById($order->payment_id) / (($vat + 100) / 100), 2);
        
        if ($locale == 'cs' && $totalPrice != round($totalPrice,0)) {
            $api->addItem(
                array(
                    'name' => 'Zaokrouhlení',
                    'description' => '',
                    'quantity' => 1,
                    'unit' => 'ks',
                    'tax' => 0,
                    'unit_price' => round((round((round($totalPrice) - $totalPrice) * 100) / 100),2)
                )
            );
        }

        if($test === true) {
            dumpe($api);
        }
        Debugger::log($api, 'sfapiSend');
        $response = $api->save();

        if ($response->error === 0) {
            //kompletne informacie o vytvorenej fakture
            $i = $response->data;
            return $i->Invoice->id;
        } else {
            Debugger::log($response->error_message);
            return null;
        }

    }

    public function getInvoices($params)
    {
        $api = new \SFAPIclient($this->email, $this->apiToken, '', 'API', $this->companyId);
        $invoices = $api->invoices();
        return $invoices;
    }

    public function getInvoice($sfId, $langId = 'slo') //slo,cze
    {
        $api = new \SFAPIclient($this->email, $this->apiToken, '', 'API', $this->companyId);
        $response = $api->getPDF($sfId,$this->apiToken,$langId);
        //Debugger::log($response, 'sfapidownloadInvoice');
        //dump($response);
        return $response;
    }

    public function sendInvoice($invoiceId, $email)
    {
        $api = new \SFAPIclient($this->email, $this->apiToken, '', 'API', $this->companyId);
        $data = [
            'invoice_id' => $invoiceId,
            'to' => $email
        ];
        Debugger::log(serialize($data), 'sfemail');
        $response = $api->sendInvoiceEmail($data);
        Debugger::log(serialize($response), 'sfemailresponse');
        if ($response->error != 0) {
            Debugger::log($response->error_message, 'sfemail');
        }
    }

}