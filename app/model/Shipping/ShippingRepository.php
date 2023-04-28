<?php

namespace App\Model\Shipping;

use App\Model\BaseRepository;
use App\Model\LocaleRepository;
use App\Model\Setting\SettingRepository;
use App\Model\User\UserRepository;
use Nette\Database\Context;
use Nette\Security\User;
use Nette\Utils\Strings;

class ShippingRepository extends BaseRepository {

    const COURIER = 1, PERSONAL = 2, PACKETA = 3;
    const LEVELS_NO = 1, LEVELS_PRICE = 2, LEVELS_WEIGHT = 3;

    private $localeRepository;

    private $levelsLang = [
        'cs' => [
            self::LEVELS_NO => 'Nepoužívat',
            self::LEVELS_PRICE => 'Podle ceny',
            self::LEVELS_WEIGHT => 'Podle váhy'
        ],
        'sk' => [
            self::LEVELS_NO => 'Nepoužívať',
            self::LEVELS_PRICE => 'Podľa ceny',
            self::LEVELS_WEIGHT => 'Podľa váhy'
        ]
    ];
    public $types = [
        self::COURIER => 'Kurýr',
        self::PERSONAL => 'Osobí odběr',
        self::PACKETA => 'Zásilkovna'
    ];
    private $db, $table = 'shipping', $settingRepository;
    private UserRepository $userRepository;
    private User $user;

    public function __construct(Context $db, SettingRepository $settingRepository, LocaleRepository $localeRepository, UserRepository $userRepository, User $user) {
        $this->db = $db;
        $this->settingRepository = $settingRepository;
        $this->localeRepository = $localeRepository;
        $this->userRepository = $userRepository;
        $this->user = $user;
    }

    public function getAll($countryId = null) {
        return $this->db->table($this->table);
    }

    public function getRelevantMethodsForSelect($countryId = null, $price) {
        $currencyId = $this->localeRepository->getCurrencyIdByLangId($this->langId());
        $symbol = $this->localeRepository->getCurrencySymbolByLangId($this->langId());
        $out = [];
        $shippings = $this->getAll()->where('enabled', 1)->where('locale_id', $currencyId);
        foreach ($shippings as $shipping) {
            $price = $this->isFree($price, $currencyId) ? 0 : $this->getPrice($price, $shipping->id);
            $out[$shipping->id] = $shipping->name.' ('.number_format($price, 2).' '.$symbol.')';
        }
        return $out;
    }

    public function getById($id) {
        return $this->getAll()->where('id', $id);
    }

    public function getNameById($id) {
        return $this->getAll()->fetchAll()[$id]['name'];
    }

    public function getPriceById($id, $price) {
        return $this->isFree($price, $this->langId()) ? 0 : $this->getAll()->fetchAll()[$id]['price'];
    }

    public function getTypes() {
        return $this->types;
    }

    public function add($values) {
        $payments = $values['payments'];
        unset($values['payments']);
        $newId = $this->db->table($this->table)->insert($values);
        $this->updateShippingPayment($newId->id, $payments);
    }

    public function update($id, $values) {
        $payments = $values['payments'];
        unset($values['payments']);
        $this->updateShippingPayment($id, $payments);
        $this->db->table($this->table)->where('id', $id)->update($values);
    }

    public function updateShippingPayment($shippingId, $payments)
    {
        $this->db->table('shipping_payment')->where('shipping_id', $shippingId)->delete();
        foreach ($payments as $payment) {
            $this->db->table('shipping_payment')->insert([
                'shipping_id' => $shippingId,
                'payment_id' => $payment
            ]);
        }
    }

    public function getPaymentMethods($shippingId)
    {
        return $this->db->table('shipping_payment')->where('shipping_id', $shippingId)->fetchPairs('payment_id', 'payment_id');
    }

    public function addLevel($shippingId, $from, $to, $price) {
        $this->db->table('shipping_levels')->insert([
            'from' => $from,
            'to' => $to,
            'price' => $price,
            'shipping_id' => $shippingId
        ]);
    }

    public function updateLevel($id, $from, $to, $price) {
        $this->db->table('shipping_levels')->where('id', $id)->update([
            'from' => $from,
            'to' => $to,
            'price' => $price
        ]);
    }

    public function getLevelsToSelect($langId) {
        $lang = $this->db->table('locale')->where('lang_id', $langId)->fetch();
        return $this->levelsLang[$lang->lang->locale];
    }

    public function getPrice($price, $shippingId) {
        $shipping = $this->getById($shippingId)->fetch();
        if($this->isFree($price, $this->localeRepository->getCurrencyIdByLangId($this->langId()))) {
            return 0;
        }
        if (!$shipping) {
            return 0;
        }
        $currencyId = $this->localeRepository->getCurrencyId();
        $aUser = $this->userRepository->getById($this->user->id)->fetch();
        if($aUser->country_id == 2 && strlen($aUser->dic) > 0 && Strings::startsWith($aUser->dic, 'CZ') && $currencyId == 2) {
            return $shipping->price / ((100 + $shipping->vat)/100);
        }
        if($aUser->country_id > 2 && strlen($aUser->icdph) > 0 && $currencyId == 1) {
            return $shipping->price / ((100 + $shipping->vat)/100);
        }
        if ($shipping->levels == self::LEVELS_NO) {
            return $shipping->price;
        }
        $levels = $this->db->table('shipping_levels')->where('from <= ?', $price)->where('to > ?', $price)->fetch();
        if (!$levels) {
            return $shipping->price;
        }
        return $levels->price;
    }

    public function getLevels($shippingId) {
        return $this->db->table('shipping_levels')->where('shipping_id', $shippingId);
    }

    public function getForSelect() {
        $shippings = $this->getAll();

        $out = $shippings->fetchPairs('id', 'name');
        return $out;
    }

    private function isFree($price, $currencyId)
    {
        $locale = $this->localeRepository->getLocaleByCurrencyId($currencyId);
        $locale = str_replace('cs', 'cz', $locale);
        $freeShipping = $this->settingRepository->getFreeDelivery(strtoupper($locale));
        $freeDeliveryEnabled = $this->settingRepository->getValue('freeDeliveryEnabled');
        if ($freeDeliveryEnabled == 0){
            return false;
        }
        return $price >= $freeShipping;
    }

}
