<?php

namespace App\Model\Payment;

use Nette\Utils\DateTime;
use Tracy\Debugger;

class Payment24payService
{
    private static $mid = 'fg8uBOvn';
    private static $eshopId = '685';
    private static $key = '737242734E3470366F767934786B687A6D74526D51685974444446586B467251';
    private static $url = 'https://admin.24-pay.eu/pay_gate/paygt';
    private static $rurl = [1 => 'https://www.app.sk/card/finish', 2 => 'https://www.app.cz/card/finish'];
    private static $nurl = [1 => 'https://www.app.sk/card/notify', 2 => 'https://www.app.cz/card/notify'];

    private $cac = [
        1 => 'EUR',
        2 => 'CZK'
    ];

    private $eshop = [
        1 => 685,
        2 => 840
    ];

    private $country = [
        1 => 'SVK',
        2 => 'CZE'
    ];

    private $lang = [
        1 => 'sk',
        2 => 'cz'
    ];

    private $cnc = [
        1 => 978,
        2 => 203
    ];

    private function sign($message)
    {
        $hash = hash("sha1", $message, true);
        $iv = self::$mid . strrev(self::$mid);
        $key = pack('H*', self::$key);
        $encrypted = openssl_encrypt($hash, 'AES-256-CBC', $key, 1, $iv);
        return strtoupper(bin2hex(substr($encrypted, 0, 16)));
    }

    public function getForm($order)
    {
        $message = self::$mid.number_format($order->price, 2, '.', '').$this->cac[$order->locale_id].$order->number.$order->firstName.$order->lastName.$order->timestamp->format('Y-m-d H:i:s');
        $sign = $this->sign($message);
        return array(
            'url' => self::$url,
            'RURL' => self::$rurl[$order->locale_id],
            'NURL' => self::$nurl[$order->locale_id],
            'Amount' => number_format($order->price, 2, '.', ''),
            'CurrAlphaCode' => $this->cac[$order->locale_id],
            'MsTxnId' => $order->number,
            'FirstName' => $order->firstName,
            'FamilyName' => $order->lastName,
            'Email' => $order->email,
            'Country' => $this->country[$order->country_id],
            'LangCode' => strtoupper($this->lang[$order->locale_id]),
            'ClientId' => self::$eshopId,
            'Timestamp' => $order->timestamp->format('Y-m-d H:i:s'),
            'Sign' => $sign,
            'Mid' => self::$mid,
            'EshopId' => $this->eshop[$order->locale_id]
        );
    }

}