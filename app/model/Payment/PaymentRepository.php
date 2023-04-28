<?php


namespace App\Model\Payment;


use App\Model\BaseRepository;
use App\Model\LocaleRepository;
use App\Model\User\UserRepository;
use Nette\Database\Context;
use Nette\Security\User;
use Nette\Utils\Strings;
use PaySys\CardPay\IButtonFactory;
use PaySys\CardPay\Payment;
use PaySys\CardPay\Security\Request;

class PaymentRepository extends BaseRepository
{

    const CASH = 1, COD = 2, TRANSFER = 3, CARD = 4;

    public $types = [
        self::CASH => 'Hotovost',
        self::COD => 'Dobírka',
        self::TRANSFER => 'Bankovní převod',
        self::CARD => 'Platba kartou'
    ];

    private $db, $table = 'payment', $localeRepository;

    /** @var IButtonFactory */
    private $cardPayButtonFactory;

    /** @var Request */
    public $cardPayRequest;
    private UserRepository $userRepository;
    private User $user;

    public function __construct(Context $db, IButtonFactory $cardPayButtonFactory, Request $cardPayRequest, LocaleRepository $localeRepository, UserRepository $userRepository, User $user)
    {
        $this->db = $db;
        $this->cardPayButtonFactory = $cardPayButtonFactory;
        $this->cardPayRequest = $cardPayRequest;
        $this->localeRepository = $localeRepository;
        $this->userRepository = $userRepository;
        $this->user = $user;
    }

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getRelevantMethodsForSelect($shippingId = null)
    {
        $currencyId = $this->localeRepository->getCurrencyIdByLangId($this->langId());
        $symbol = $this->localeRepository->getCurrencySymbolByLangId($this->langId());
        /*
        if ($shippingId === null) {
            return [];
        }
        */
        $out = [];
        $relevant = $this->db->table('shipping_payment')->where('shipping_id', $shippingId)->fetchPairs('payment_id', 'payment_id');
        $payments = $this->getAll()->where('enabled', 1)->where('id', $relevant);
        foreach ($payments as $payment) {
            $price = $payment->price;
            $aUser = $this->userRepository->getById($this->user->id)->fetch();
            if($aUser->country_id == 2 && strlen($aUser->dic) > 0 && Strings::startsWith($aUser->dic, 'CZ') && $currencyId == 2) {
                $price = $payment->price / ((100 + $payment->vat)/100);
            }
            if($aUser->country_id > 2 && strlen($aUser->icdph) > 0 && $currencyId == 1) {
                $price = $payment->price / ((100 + $payment->vat)/100);
            }

            $out[$payment->id] = $payment->name.' ('.number_format($price, 2).' '.$symbol.')';
        }
        return $out;
    }

    public function getById($id)
    {
        return $this->getAll()->where('id', $id);
    }

    public function getTypeById($id)
    {
        return $this->getAll()->fetchAll()[$id]['type'];
    }

    public function getNameById($id)
    {
        return $this->getAll()->fetchAll()[$id]['name'];
    }

    public function getPriceById($id)
    {
        $payment = $this->getById($id)->fetch();
        $currencyId = $this->localeRepository->getCurrencyIdByLangId($this->langId());
        $price = $payment->price;
        $aUser = $this->userRepository->getById($this->user->id)->fetch();
        if($aUser->country_id == 2 && strlen($aUser->dic) > 0 && Strings::startsWith($aUser->dic, 'CZ') && $currencyId == 2) {
            $price = $payment->price / ((100 + $payment->vat)/100);
        }
        if($aUser->country_id > 2 && strlen($aUser->icdph) > 0 && $currencyId == 1) {
            $price = $payment->price / ((100 + $payment->vat)/100);
        }
        return $price;
    }

    public function isCod($paymentId)
    {
        $type = $this->getTypeById($paymentId)->fetch();
        if($type) {
            return $type->type == self::COD;
        }
        return false;
    }

    public function getPaymentType($paymentId)
    {
        $payment = $this->db->table($this->table)->where('id', $paymentId)->fetch();
        return $payment ? $payment->type : null;
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function add($values)
    {
        $this->db->table($this->table)->insert($values);
    }

    public function update($id, $values)
    {
        $this->db->table($this->table)->where('id', $id)->update($values);
    }


    public function trustpayRedirect($id, $price, $email)
    {

        $baseUrl = "https://playground.trustpay.eu/mapi5/Card/pay";
        $baseUrl = 'https://ib.trustpay.eu/mapi5/Card/Pay';
        $AID = 111;
        $secretKey = 'aaa';
        $AMT = round($price, 2);
        $CUR = "EUR";
        $REF = $id;

        $sigData = sprintf("%d%s%s%s", $AID, number_format($AMT, 2, '.', ''), $CUR, $REF);

        $SIG = strtoupper(hash_hmac('sha256', pack('A*', $sigData), pack('A*', $secretKey)));

        $url = sprintf(
            "%s?AID=%d&AMT=%s&CUR=%s&REF=%s&SIG=%s&EMA=%s&LNG=sk",
            $baseUrl, $AID, number_format($AMT, 2, '.', ''), $CUR, urlencode($REF), $SIG, urlencode($email));
        header("Location: $url");
        exit();
   }
   
    public function getForSelect() {
        $payment = $this->getAll();

        $out = $payment->fetchPairs('id', 'name');
        return $out;
    }

    /**
     * Tatra bank - cardPay
     *
     * @param $id
     * @param $price
     * @param $email
     */
    public function cardpayRedirect($id, $price, $email)
    {
        $payment = new Payment($price, $id, $email);
        $component = $this->cardPayButtonFactory->create($payment);
        $component->onPayRequest[] = function ($payment) {
            header("Location: " . $this->cardPayRequest->getUrl($payment));
        };
        $component->handlePay();
        exit();
    }

}