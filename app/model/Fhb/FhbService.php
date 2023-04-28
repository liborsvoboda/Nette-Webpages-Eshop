<?php


namespace App\Model\Fhb;


use App\Model\Order\OrderRepository;
use App\Model\Payment\PaymentRepository;
use App\Model\Product\ProductRepository;
use GuzzleHttp\Client;
use Nette\Application\LinkGenerator;
use Nette\Database\Context;
use Tracy\Debugger;
use App\Model\Country\CountryRepository;

class FhbService
{
    private
        $appId = '008d74435e58e04ce83799a4f1402fc7',
        $secret = 'Npt3hrnvfXDtwU0395137d5fbe0n68xo2tauak3t_bad',
        $address = 'https://api.fhb.sk/v3/',
        $addressDev = 'https://api-dev.fhb.sk/v3/';
    /**
     * @var Context
     */
    private Context $db;
    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * @var LinkGenerator
     */
    private LinkGenerator $linkGenerator;
    /**
     * @var PaymentRepository
     */
    private PaymentRepository $paymentRepository;

    private CountryRepository $countryRepository;

    public function __construct(Context $db,
                                ProductRepository $productRepository,
                                LinkGenerator $linkGenerator,
                                PaymentRepository $paymentRepository,
                                CountryRepository $countryRepository)
    {
        $this->db = $db;
        $this->productRepository = $productRepository;
        $this->linkGenerator = $linkGenerator;
        $this->paymentRepository = $paymentRepository;
        $this->countryRepository = $countryRepository;
    }

    private function login()
    {
        $client = $this->getClient();
        $data = [
            'app_id' => $this->appId,
            'secret' => $this->secret
        ];
        try {
            $response = $client->request('POST', 'login', ['json' => $data]);
        } catch (\Exception $e) {
            return false;
        }
        $res = json_decode((string)$response->getBody(), true);
        return $res['token'];
    }

    private function getClient()
    {
        $client = new Client(['base_uri' => $this->address, 'versionl' => 1.1]);
        return $client;
    }

    public function sendProduct($productId, $update = true)
    {
        $product = $this->productRepository->getById($productId)->fetch();
        $method = $update === true ? 'PUT' : 'POST';
        $uri = $update === true ? 'product/?id='.$product->id : 'product';
        $data = [
            'name' => $product->name,
            'ean' => $product->ean,
            'photo' => $this->linkGenerator->link('Front:Homepage:default') . substr($product->image, 1),
            'notify_url' => $this->linkGenerator->link('Front:Fhb:product', ['id' => $product->id])
        ];
        if($update === false) {
            $data['id'] = $product->id;
        }
        $token = $this->login();
        $headers = [
            'X-Authentication-Simple' => base64_encode($token)
        ];
        $client = $this->getClient();
        try {
            $response = $client->request($method, $uri, ['headers' => $headers, 'json' => $data]);
            Debugger::log((string)$response->getBody(), 'fhb-send-log');
        } catch (\Exception $e) {
            Debugger::log($e->getMessage(), 'fhb-send-log');
        }
    }

    public function sendOrder($order, $orderItems)
    {
//        $order = $this->orderRepository->getById($orderId)->fetch();
//        $orderItems = $this->orderRepository->getOrderItems($orderId)->fetchAll();
        $deliveryId = '';
        $cod = $this->paymentRepository->getTypeById($order->payment_id) == PaymentRepository::COD ? $order->price : 0;
        $items = [];
        foreach ($orderItems as $orderItem) {
            if($this->checkComboProduct($orderItem->product_id) != false) {
                $combos = $this->checkComboProduct($orderItem->product_id);
                foreach ($combos as $comboId => $comboAmount) {
                    $items[] = [
                        'id' => $comboId,
                        'quantity' => $comboAmount * $orderItem->count
                    ];
                }
            } else {
                $items[] = [
                    'id' => $orderItem->product_id,
                    'quantity' => $orderItem->count
                ];
            }
        }
        $notification = [
            'confirmed' => $this->linkGenerator->link('Front:Fhb:confirmed', ['id' => $order->id]),
            'sent' => $this->linkGenerator->link('Front:Fhb:sent', ['id' => $order->id]),
            'delivered' => $this->linkGenerator->link('Front:Fhb:delivered', ['id' => $order->id]),
            'returned' => $this->linkGenerator->link('Front:Fhb:returned', ['id' => $order->id])
        ];
        if($order->otherAddress == 1) {
            $address = [
                'name' => $order->otherName . ' ' . $order->otherSurname,
                'street' => $order->otherStreet,
                'city' => $order->otherCity,
                'zip' => $order->otherZip,
                'country' => isset($order->otherCountry) ? strtoupper($this->countryRepository->getCodeById($order->otherCountry)) : null
            ];
        } else {
            $address = [
                'name' => $order->firstName . ' ' . $order->lastName,
                'street' => $order->street,
                'city' => $order->city,
                'zip' => $order->zip,
                'country' => strtoupper($order->country->code)
                ];
        }
        $data = [
            'id' => $order->id,
            'variable_symbol' => $order->number,
            'parcel_service' => $deliveryId,
            'cod' => ($order->otherAddress == 1) ? ($order->otherCountry == 2 ) ? round( $cod,0) : $cod : (($order->country->code == "cz") ? round( $cod,0) : $cod),
            'value' => ($order->otherAddress == 1) ? ($order->otherCountry == 2 ) ? round( $order->price,0) : $cod : (($order->country->code == "cz") ? round( $order->price,0) : $order->price),
            'note_delivery' => $order->note,
            'recipient' => [
                'address' => $address,
                'contact' => [
                    'email' => $order->email,
                    'phone' => ($order->otherPhone) ? $order->otherPhone : $order->phone
                ]
            ],
            'items' => $items,
            'notification' => $notification
        ];
        $token = $this->login();
        $headers = [
            'X-Authentication-Simple' => base64_encode($token)
        ];
        $client = $this->getClient();
       Debugger::log(['headers' => $headers, 'json' => $data], 'fhb-send');
        try {
            $response = $client->request('POST', 'order', ['headers' => $headers, 'json' => $data]);
            Debugger::log((string)$response->getBody(), 'fhb-send-log');
            return true;
        } catch (\Exception $e) {
            Debugger::log($e->getMessage(), 'fhb-send-log');
            return false;
        }
    }

    public function sendOrderTest($json)
    {
        $data = json_decode($json, true);
        $token = $this->login();
        $headers = [
            'X-Authentication-Simple' => base64_encode($token)
        ];
        $client = $this->getClient();
        try {
            $response = $client->request('POST', 'order', ['headers' => $headers, 'json' => $data]);
            Debugger::log((string)$response->getBody(), 'fhb-send-log');
        } catch (\Exception $e) {
            Debugger::log($e->getMessage(), 'fhb-send-log');
        }
    }

    public function getParcelServices()
    {
        $token = $this->login();
        $client = $this->getClient();
        $headers = [
            'X-Authentication-Simple' => base64_encode($token)
        ];
        $response = $client->request('GET', 'parcel-service', ['headers' => $headers]);
        $res = json_decode($response->getBody(), true);
        dumpe($res);
    }

    public function exportProducts()
    {
        $products = $this->productRepository->getAll();
        foreach ($products as $product) {
            $this->sendProduct($product->id);
        }
    }

    public function checkComboProduct($productId)
    {
        $comboProduct = $this->productRepository->getComboProducts($productId)->fetchAll();
        if(!$comboProduct) {
            return false;
        }
        $out = [];
        foreach ($comboProduct as $item) {
            $out[$item->combo_id] = $item->amount;
        }
        return $out;
    }

    public function saveTrackingLink($orderId)
    {
        $token = $this->login();
        $client = $this->getClient();
        $headers = [
            'X-Authentication-Simple' => base64_encode($token)
        ];
        $response = $client->request('GET', 'order/?id=' . $orderId, ['headers' => $headers]);
        $res = json_decode($response->getBody(), true);
        if(count($res['tracking_links']) > 0) {
            $this->db->table('orders')->where('id', $orderId)->update(['tracking_link' => $res['tracking_links'][0]]);
            return $res['tracking_links'][0];
        }
    }
}