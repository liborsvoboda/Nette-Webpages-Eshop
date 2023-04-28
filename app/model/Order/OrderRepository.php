<?php


namespace App\Model\Order;


use App\Model\BaseRepository;
use App\Model\Cart\CartRepository;
use App\Model\Commission\CommissionRepository;
use App\Model\Country\CountryRepository;
use App\Model\Customer\CustomerRepository;
use App\Model\Email\EmailService;
use App\Model\Fhb\FhbService;
use App\Model\Isklad\IskladService;
use App\Model\LocaleRepository;
use App\Model\Payment\PaymentRepository;
use App\Model\Services\PdfService;
use App\Model\Setting\SettingRepository;
use App\Model\Shipping\ShippingRepository;
use App\Model\SuperFaktura\SuperFakturaService;
use App\Model\User\UserRepository;
use App\Model\Voucher\VoucherRepository;
use Nette\Database\Context;
use Nette\Http\Session;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Localization\ITranslator;
use Tracy\Debugger;

class OrderRepository extends BaseRepository
{
    private $session,
        $section,
        $paymentRepository,
        $shippingRepository,
        $countryRepository,
        $cartRepository,
        $userRepository,
        $db,
        $table,
        $pdfService,
        $settingRepository,
        $emailService,
        $voucherRepository,
        $discount = null,
        $translator,
        $localeRepository,
        $iskladService,
        $fhbService,
        $superFakturaService,
        $customerRepository,
        $commissionRepository;

    public function __construct(
        Session $session,
        PaymentRepository $paymentRepository,
        ShippingRepository $shippingRepository,
        CountryRepository $countryRepository,
        CartRepository $cartRepository,
        UserRepository $userRepository,
        Context $db,
        PdfService $pdfService,
        EmailService $emailService,
        SettingRepository $settingRepository,
        ITranslator $translator,
        LocaleRepository $localeRepository,
        IskladService $iskladService,
        SuperFakturaService $superFakturaService,
        VoucherRepository $voucherRepository,
        FhbService $fhbService,
        CommissionRepository $commissionRepository,
        CustomerRepository $customerRepository
    )
    {
        $this->session = $session;
        $this->paymentRepository = $paymentRepository;
        $this->shippingRepository = $shippingRepository;
        $this->countryRepository = $countryRepository;
        $this->cartRepository = $cartRepository;
        $this->userRepository = $userRepository;
        $this->settingRepository = $settingRepository;
        $this->translator = $translator;
        $this->db = $db;
        $this->pdfService = $pdfService;
        $this->table = 'orders';
        $this->emailService = $emailService;
        $this->localeRepository = $localeRepository;
        $this->iskladService = $iskladService;
        $this->section = $session->getSection('orderData');
        $this->superFakturaService = $superFakturaService;
        $this->voucherRepository = $voucherRepository;
        $this->fhbService = $fhbService;
        $this->commissionRepository = $commissionRepository;
        $this->customerRepository = $customerRepository;
    }

    const INVOICE_PROFORMA = 1,
        INVOICE_REGULAR = 2,
        INVOICE_STORNO = 3;

    const STATUS_NEW = 1,
        STATUS_PROCESSING = 2,
        STATUS_INVOICE = 3,
        STATUS_SENT = 4,
        STATUS_DOBROPIS = 5,
        STATUS_STORNO = 6,
        STATUS_DELIVERED = 7,
        STATUS_RETURNED = 8;

    const DEFAULT_PROFIT = 1.3;

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getById($id)
    {
        return $this->getAll()->where('id', $id);
    }

    public function getActive()
    {
        return $this->getAll()
            ->where('order_status_id != ?', self::STATUS_STORNO);
    }

    public function getAllSumPrice($czk = false)
    {
        $out = $this->getActive();
        if($czk) {
            $out->where('locale_id = ?', 2);
        } else {
            $out->where('locale_id != ?', 2);
        }
        return $out->sum('price');
    }

    public function getTodaySumPrice($czk = false)
    {
        $now = new DateTime();
        $out = $this->getActive()
            ->where('timestamp >=', $now->format('Y-m-d') . ' 00:00:00')
            ->where('timestamp <=', $now->format('Y-m-d') . ' 23:59:59');
        if($czk) {
            $out->where('locale_id = ?', 2);
        } else {
            $out->where('locale_id != ?', 2);
        }
        return $out->sum('price');
    }

    public function getWeekSumPrice($czk = false)
    {
        $firstDay = new DateTime();
        $lastDay = new DateTime();
        $firstDay->modify('this week')->setTime(0, 0, 0);
        $lastDay->modify('this week +6 days')->setTime(23, 59, 59);
        $out = $this->getActive()
            ->where('timestamp >=', $firstDay)
            ->where('timestamp <=', $lastDay);
        if($czk) {
            $out->where('locale_id = ?', 2);
        } else {
            $out->where('locale_id != ?', 2);
        }
        return $out->sum('price');
    }

    public function getMonthSumPrice($czk = false)
    {
        $now = new DateTime();
        $out = $this->getActive()
            ->where('timestamp >=', $now->format('Y-m') . '-01 00:00:00')
            ->where('timestamp <=', $now->format('Y-m-t') . ' 23:59:59');
        if($czk) {
            $out->where('locale_id = ?', 2);
        } else {
            $out->where('locale_id != ?', 2);
        }
        return $out->sum('price');
    }

    public function getYearSumPrice($czk = false)
    {
        $now = new DateTime();
        $out = $this->getActive()
            ->where('timestamp >=', $now->format('Y') . '-01-01 00:00:00')
            ->where('timestamp <=', $now->format('Y') . '-12-31 23:59:59');
        if($czk) {
            $out->where('locale_id = ?', 2);
        } else {
            $out->where('locale_id != ?', 2);
        }
        return $out->sum('price');
    }

    public function getTodayProfitability()
    {
        $now = new DateTime();
        $orders = $this->getActive()
            ->where('timestamp >=', $now->format('Y-m-d') . ' 00:00:00')
            ->where('timestamp <=', $now->format('Y-m-d') . ' 23:59:59')
            ->select('id')->fetchAll();
        $profitability = $this->getProfitability($orders);
        return $profitability;
    }

    public function getAllProfitability()
    {
        $orders = $this->getActive()
            ->select('id')->fetchAll();
        $profitability = $this->getProfitability($orders);
        return $profitability;
    }

    public function getWeekProfitability()
    {
        $firstDay = new DateTime();
        $lastDay = new DateTime();
        $firstDay->modify('this week')->setTime(0, 0, 0);
        $lastDay->modify('this week +6 days')->setTime(23, 59, 59);
        $orders = $this->getActive()
            ->where('timestamp >=', $firstDay)
            ->where('timestamp <=', $lastDay)
            ->select('id')->fetchAll();
        $profitability = $this->getProfitability($orders);
        return $profitability;
    }

    public function getMonthProfitability()
    {
        $now = new DateTime();
        $orders = $this->getActive()
            ->where('timestamp >=', $now->format('Y-m') . '-01 00:00:00')
            ->where('timestamp <=', $now->format('Y-m-t') . ' 23:59:59')
            ->select('id')->fetchAll();
        $profitability = $this->getProfitability($orders);
        return $profitability;
    }

    public function getYearProfitability()
    {
        $now = new DateTime();
        $orders = $this->getActive()
            ->where('timestamp >=', $now->format('Y') . '-01-01 00:00:00')
            ->where('timestamp <=', $now->format('Y') . '-12-31 23:59:59')
            ->select('id')->fetchAll();
        $profitability = $this->getProfitability($orders);
        return $profitability;
    }

    public function getProfitability($orders)
    {
        $purchasePrice = 0;
        $sellPrice = 0;
        $orderItems = $this->db->table('order_item')->where('order_id', $orders)->fetchAll();
        foreach ($orderItems as $orderItem) {
            $sellPrice += $orderItem->price * $orderItem->count;
            $purchasePrice += ($orderItem->product->origPriceVat / self::DEFAULT_PROFIT) * $orderItem->count;
        }
        $profitability = round($sellPrice - $purchasePrice, 2);
        return $profitability;
    }

    public function saveDataToSession(array $values)
    {
        $values['locale_id'] = $this->localeRepository->getIdByLangId($this->langId());
        $values['countryName'] = $this->countryRepository->getNameById($values['country']);
        $values['shippingName'] = $this->shippingRepository->getNameById($values['shipping']);
        $values['paymentName'] = $this->paymentRepository->getNameById($values['payment']);
        $values['shippingPrice'] = round($this->shippingRepository->getPrice($this->cartRepository->getTotalPrice(), $values['shipping']), 2);
        $values['paymentPrice'] = round($this->paymentRepository->getPriceById($values['payment']), 2);
        $values['otherCountryName'] = ($values['otherCountry']) ? $this->countryRepository->getNameById($values['otherCountry']) : null;
        $this->section->orderData = $values;
    }

    public function getDataFromSession()
    {
        if (isset($this->section->orderData)) {
            return $this->section->orderData;
        }
    }

    public function removeDataFromSession()
    {
        unset($this->section->orderData);
    }

    public function isActiveOrder()
    {
        return isset($this->section->orderData) ? true : false;
    }

    public function setPaid($orderNumber)
    {
        $this->db->table($this->table)->where('number', $orderNumber)->update(['isPaid' => 1]);
    }

    public function setPaidStatus($id, $status)
    {
        $order = $this->getById($id)->fetch();
        if($order->isPaid == true) {
            return;
        }
        $order->update(['isPaid' => true]);
        //$this->commissionRepository->addCommissions($order);
    }

    public function setPaidBySfId($sfId)
    {
        $order = $this->db->table('orders')->where('sfId', $sfId)->where('isPaid', false)->fetch();
        if ($order) {
            $this->setPaid($order->number);
            $items = $this->getOrderItems($order->id);
            $this->fhbService->sendOrder($order, $items);
        }
    }


    public function makeOrder()
    {
        $isCardPayment = false;
        $orderData = $this->getDataFromSession();
        $orderNumber = $this->makeOrderNumber();
        $orderData['number'] = $orderNumber;
        $items = $this->cartRepository->getItems();
        $orderData['price'] = $this->calculatePrice($orderData);
        if($this->discount !== null) {
            $orderData['isVoucher'] = true;
            //$orderData['voucherId'] = $this->voucherRepository->getVoucherId();
            $orderData['voucherCode'] = $this->voucherRepository->getVoucherCode();
            $orderData['discount'] = $this->discount;
        }
        $orderId = $this->saveOrder($orderData);
        $this->saveOrderItems($orderId);
        $this->changeUserGroupId();
        $orderData['orderId'] = $orderId->id;
        $this->emailService->sendOrderEmail($orderData, $items, $this->translator->translate('email.order_received'));
        $newOrder = $this->getById($orderId)->fetch();
        $newOrderItems = $this->getOrderItems($orderId)->fetchAll();
        switch ($this->paymentRepository->getTypeById($orderData['payment'])) {
            case PaymentRepository::COD:
                $sfId = $this->superFakturaService->sendNewOrder($newOrder, $newOrderItems, $orderData['locale_id']);
                $this->db->table('orders')->where('id', $orderId)->update(['sfId' => $sfId]);
                $fhbId = $this->fhbService->sendOrder($newOrder, $newOrderItems);
                if($fhbId == true) {
                    $this->db->table('orders')->where('id', $orderId)->update(['fhbId' => $fhbId]);
                }
                break;
            case PaymentRepository::CARD:
                $isCardPayment = true;
                break;
            case PaymentRepository::TRANSFER:
                $sfId = $this->superFakturaService->sendNewOrder($newOrder, $newOrderItems, $orderData['locale_id'], false);
                $this->db->table('orders')->where('id', $orderId)->update(['sfId' => $sfId]);
                $this->superFakturaService->sendInvoice($sfId, $newOrder->email);
                break;
        }
        $this->saveDataToSession($orderData);
        return $isCardPayment;
    }

    public function getOrderItems($orderId)
    {
        return $this->db->table('order_item')->where('order_id', $orderId);
    }

    public function saveOrder($orderData)
    {
        unset($orderData['countryName'], $orderData['shippingName'], $orderData['paymentName']);
        $orderData['user_id'] = $this->userRepository->getId();
        $orderData['country_id'] = $orderData['country'];
        $orderData['shipping_id'] = $orderData['shipping'];
        $orderData['payment_id'] = $orderData['payment'];
        $orderData['timestamp'] = new DateTime();
        $orderData['order_status_id'] = 1;
        $orderData['exchange_rate_eur'] = $this->localeRepository->getExchangeRate(2);
        unset($orderData['country'],$orderData['otherCountryName'], $orderData['shipping'], $orderData['payment'], $orderData['shippingPrice'], $orderData['paymentPrice']);

        unset($orderData['isVoucher']);
        $referrals = $this->userRepository->getAllParents($orderData['user_id']);
        $orderData['referrals'] = json_encode($referrals);
        unset($orderData['orderId']);
        $orderId = $this->db->table($this->table)->insert($orderData);
        return $orderId;
    }

    public function update($values, $orderId)
    {
        $this->db->table($this->table)->where('id', $orderId)->update($values);
    }

    public function updatePrice($orderId, $price)
    {
        $this->db->table($this->table)->where('id', $orderId)->update(['price' => $price]);
    }

    public function saveOrderItemFromProduct($orderId, $product, $count)
    {
        $orderItem = [
            'order_id' => $orderId,
            'product_id' => $product->id,
            'count' => $count,
            'price' => $product->priceVat,
            'exchange_rate_eur' => $this->localeRepository->getExchangeRate(2)
        ];
        $this->db->table('order_item')->insert($orderItem);
    }

    private function saveOrderItems($orderId)
    {
        $items = $this->cartRepository->getItems();
        foreach ($items as $item) {
            $orderItem = [
                'order_id' => $orderId,
                'product_id' => $item['id'],
                'count' => $item['amount'],
                'price' => $item['price'],
                'commissions' => $item['commissions'],
                'exchange_rate_eur' => $this->localeRepository->getExchangeRate(2),
                'freeAmount' => $item['freeAmount']
            ];
            $this->db->table('order_item')->insert($orderItem);
        }
    }

    public function makeOrderNumber()
    {
        $from = new DateTime();
        $to = new DateTime();
        $from = $from->setTime(0, 0, 0);
        $from = $from->setDate($from->format('Y'), $from->format('m'), 1);
        $to = $to->setTime(23, 59, 59);
        $fromDate = $from->format('Y-m-d H:i:s');
        $toDate = $to->format('Y-m-t H:i:s');
        $last = $this->db->table($this->table)->order('id DESC')
            ->where('timestamp >= ?', $fromDate)
            ->where('timestamp <= ?', $toDate)
            ->fetch();
        if ($last) {
            return $last->number + 1;
        } else {
            $now = new DateTime();
            return $now->format('y') . $now->format('m') . '0001';
        }
    }

    private function calculatePrice($orderData)
    {
        $price = $this->cartRepository->getTotalPrice();
        $itemsComPrice = $this->cartRepository->getComItemsPrice(false);
        $this->discount = $this->voucherRepository->getDiscount($itemsComPrice);
        if($this->discount) {
            $price = $price - $this->discount;
        }
        $price += $this->shippingRepository->getPrice($price, $orderData['shipping']);
        $price += $this->paymentRepository->getPriceById($orderData['payment']);
        return $price;
    }


    public function getByUser($userId)
    {
        return $this->getAll()->where('user_id', $userId)->order('id DESC');
    }

    public function saveInvoice($number, $type, $orderId)
    {
        $this->db->table('invoice')->insert([
            'orders_id' => $orderId,
            'number' => $number,
            'type' => $type
        ]);
    }

    public function makeInvoiceNumber($orderId, $type)
    {
        $number = 1;
        $check = $this->db->table('invoice')->where('orders_id', $orderId)->where('type', $type)->fetch();
        if ($check) {
            return false;
        }
        $last = $this->db->table('invoice')->where('type', $type)->order('id DESC')->fetch();
        if ($last) {
            $number = substr($last->number, -4) + 1;
        }
        switch ($type) {
            case self::INVOICE_PROFORMA :
                $template = $this->settingRepository->getValue('invoiceNumberProforma');
                break;
            case  self::INVOICE_REGULAR :
                $template = $this->settingRepository->getValue('invoiceNumberRegular');
                break;
            case self::INVOICE_STORNO :
                $template = $this->settingRepository->getValue('invoiceNumberStorno');
        }
        if (!$template) {
            return false;
        }
        $now = new DateTime();
        $y4 = sprintf('%04d', $now->format('Y'));
        $y2 = sprintf('%02d', $now->format('y'));
        $m2 = sprintf('%02d', $now->format('m'));
        $d2 = sprintf('%02d', $now->format('d'));
        $n4 = sprintf('%04d', $number);
        $out = str_replace('RRRR', $y4, $template);
        $out = str_replace('RR', $y2, $out);
        $out = str_replace('MM', $m2, $out);
        $out = str_replace('DD', $d2, $out);
        $out = str_replace('CCCC', $n4, $out);
        return $out;
    }

    public function getInvoice($orderId, $type, $returnNumber = false)
    {
        $check = $this->db->table('invoice')->where('orders_id', $orderId)->where('type', $type)->fetch();
        if ($check) {
            $number = $check->number;
            $now = $check->timestamp;
        } else {
            $number = $this->makeInvoiceNumber($orderId, $type);
            $now = new DateTime();
        }
        if ($number === false) {
            return false;
        }
        $order = $this->getById($orderId)->fetch();

        $data = new ArrayHash();
        $data->orders_id = $orderId;
        $data->number = $number;
        $data->type = $type;
        $data->timestamp = $now;
        if (!$check) {
            $this->db->table('invoice')->insert($data);
        }
        $invoice = $this->pdfService->makeInvoice($number, $order, $type, $data);
        return $returnNumber ? $number : $invoice;
    }

    public function getOrderStatuses($localeId)
    {
        return $this->db->table('order_status')
            ->select(':order_status_lang.*')
            ->select('order_status.*')
            ->where(':order_status_lang.locale_id', $localeId);
    }

    public function getOrderStatusesForForm()
    {
        $out = [];
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $statuses = $this->db->table('order_status')
                ->select(':order_status_lang.*')
                ->select('order_status.*')
                ->where(':order_status_lang.locale_id', $locale->id);

            foreach ($statuses as $status) {
                $out[$locale->id][$status->order_status_id] = [
                    'subject' => $status->subject,
                    'email' => $status->email
                ];
            }
        }
        return $out;
    }

    public function updateOrderStatusEmail($values)
    {
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $this->db->table('order_status_lang')->where('locale_id', $locale->id)->where('order_status_id', self::STATUS_PROCESSING)
                ->update([
                    'subject' => $values['locale'.$locale->id]['name2l'.$locale->id],
                    'email' => $values['locale'.$locale->id]['email2l'.$locale->id],
                ]);
            $this->db->table('order_status_lang')->where('locale_id', $locale->id)->where('order_status_id', self::STATUS_SENT)
                ->update([
                    'subject' => $values['locale'.$locale->id]['name4l'.$locale->id],
                    'email' => $values['locale'.$locale->id]['email4l'.$locale->id],
                ]);
            $this->db->table('order_status_lang')->where('locale_id', $locale->id)->where('order_status_id', self::STATUS_STORNO)
                ->update([
                    'subject' => $values['locale'.$locale->id]['name6l'.$locale->id],
                    'email' => $values['locale'.$locale->id]['email6l'.$locale->id],
                ]);
        }
    }

    public function getOrderStatusesPairs()
    {
        $localeId = 1;
        return $this->getOrderStatuses($localeId)->fetchPairs('id', 'name');
    }

    public function setStatus($orderId, $status, $sendMail = false)
    {
        $order = $this->getById($orderId)->fetch();
        if ($order->order_status_id == self::STATUS_STORNO) {
            return;
        }
        if ($order->order_status_id != self::STATUS_SENT) {
            $this->getById($orderId)->update(['order_status_id' => $status]);
        }
        if ($sendMail) {
            $stat = $this->getOrderStatuses($order->locale_id)->where('order_status.id', $status)->fetch();

            if ($stat->email) {
                $order = $this->getById($orderId)->fetch();
                $this->emailService->sendOrderChangeStatusEmail($order->number, $order->email, $stat->email);
            }
        }
    }

    public function deleteOrderItem($id)
    {
        $this->db->table('order_item')->where('id', $id)->delete();
    }

    public function setDumped($orderId)
    {
        $this->getAll()->where('id', $orderId)->update(['dumped' => true]);
    }

    public function getDumped()
    {
        return $this->getAll()->where('dumped', true);
    }

    public function getNonDumped()
    {
        return $this->getAll()->where('dumped', false);
    }

    public function getSales($userId, $fromDate, $toDate)
    {
        $subsArray = $this->getSubsArray($userId);
        $sum = $this->getAll()
            ->where('isPaid', true)
            ->where('user_id', $subsArray)
            ->where('timestamp >= ?', $fromDate)
            ->where('timestamp <= ?', $toDate)
            ->sum('price');
        return $sum;
    }

    public function getUnpaidOrdersSum($userId, $fromDate, $toDate)
    {
        $subsArray = $this->getSubsArray($userId);
        $sum = $this->getAll()
            ->where('isPaid', false)
            ->where('user_id', $subsArray)
            ->where('timestamp >= ?', $fromDate)
            ->where('timestamp <= ?', $toDate)
            ->sum('price');
        return $sum;
    }

    public function getUnpaidOrders($userId)
    {
        $subsArray = $this->getSubsArray($userId);
        $orders = $this->getAll()
            ->where('isPaid', false)
            ->where('user_id', $subsArray)
            ->order('id DESC');
        return $orders;
    }

    public function getPaidOrders($userId)
    {
        $subsArray = $this->getSubsArray($userId);
        $orders = $this->getAll()
            ->where('isPaid', true)
            ->where('user_id', $subsArray)
            ->order('id DESC');
        return $orders;
    }

    private function getSubsArray($userId)
    {
        $subs = $this->customerRepository->getSubIds($userId);
        $subsArray = explode(',', $subs);
        array_unshift($subsArray, $userId);
        return $subsArray;
    }

    public function saveCommissions($order = null)
    {
        $order = $this->getById(16)->fetch();
        $this->commissionRepository->addCommissions($order);
    }

    public function changeUserGroupId()
    {
        $userId = $this->userRepository->getId();
        if($userId && isset($this->section['tmp_user_level_id'])) {
            $user = $this->userRepository->getById($userId)->fetch();
            $newGroupId = $this->section['tmp_user_level_id'];
            unset($this->section['tmp_user_level_id']);
            /*if($user->user_group_id == $newGroupId) {
                return;
            }*/
            $this->userRepository->setNewUserGroupId($userId, $newGroupId);
            $this->userRepository->setNewUserLevelId($userId, $newGroupId);
            $this->userRepository->loginAsUser($userId);
        }
    }

    public function getByNumber($number)
    {
        return $this->db->table($this->table)->where('number', $number);
    }

    public function sendCardOrder($number)
    {
        if(!$number) {
            return;
        }
        $newOrder = $this->getByNumber($number)->fetch();
        if(!$newOrder) {
            return;
        }
        $newOrderItems = $this->getOrderItems($newOrder->id)->fetchAll();
        $fhbId = $this->fhbService->sendOrder($newOrder, $newOrderItems);
        if($fhbId == true) {
            $this->db->table('orders')->where('id', $newOrder->id)->update(['fhbId' => $fhbId]);
            $this->superFakturaService->sendNewOrder($newOrder, $newOrderItems, $newOrder['locale_id'], false);
        }

    }
}