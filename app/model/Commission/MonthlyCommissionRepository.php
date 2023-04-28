<?php

namespace App\Model\Commission;

use App\Model\Customer\CustomerRepository;
use App\Model\LocaleRepository;
use App\Model\Order\OrderRepository;
use Nette\Database\Context;
use Nette\Utils\DateTime;

class MonthlyCommissionRepository
{
    private Context $db;
    private CustomerRepository $customerRepository;

    private const ORDER_COMMISSION =1, GROUP_COMMISSION = 2;
    private LocaleRepository $localeRepository;

    public function __construct(Context $db, CustomerRepository $customerRepository, LocaleRepository $localeRepository)
    {
        $this->db = $db;
        $this->customerRepository = $customerRepository;
        $this->localeRepository = $localeRepository;
    }

    public function getAll()
    {
        return $this->db->table('user_monthly_profit');
    }

    public function getById($id)
    {
        return $this->getAll()->where('id', $id);
    }

    public function getByUserId($userId)
    {
        return $this->getAll()->where('user_id', $userId);
    }

    public function getInMonth($month, $year)
    {
        return $this->getAll()
            ->where('month', $month)
            ->where('year', $year);
    }

    public function make($month, $year)
    {
        $users = $this->customerRepository->getAll();
        foreach ($users as $user) {
            if($user->id < 500) {
                continue;
            }
            $groupTurnover = $this->sumComGroupTurnover($user->id, $month, $year);
            $selfTurnover = $this->sumSelfMonthTurnover($user->id, $month, $year);
            if($groupTurnover == 0 && $selfTurnover == 0) {
                continue;
            }
            $test = $this->db->table('user_monthly_profit')
                ->where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->fetch();
            if($test) {
                $test->update([
                    'group_turnover' => $groupTurnover,
                    'self_turnover' => $selfTurnover
                ]);
            } else {
                $this->db->table('user_monthly_profit')->insert([
                    'group_turnover' => $groupTurnover,
                    'self_turnover' => $selfTurnover,
                    'month' => $month,
                    'year' => $year,
                    'user_id' => $user->id
                ]);
            }
        }
        $this->makeOrderCommissions($month, $year);
        $this->sumOrderCommissions($month, $year);
        die;
    }

    public function makeOrderCommissions($month, $year)
    {
        $dates = $this->makeDates($month, $year);
        $userCommission = [];
        $orders = $this->getOrders()
            ->where('timestamp >=', $dates['from'])
            ->where('timestamp <=', $dates['to']);
        foreach ($orders as $order) {
            $commissions = $this->addOrderCommission($order);
        }
    }

    public function sumOrderCommissions($month, $year)
    {
        $users = $this->customerRepository->getAll();
        foreach ($users as $user) {
            $commission = $this->getSumUserOrderCommissions($user->id, $month, $year);
            if($commission == 0) {
                continue;
            }
            $this->db->table('user_monthly_profit')
                ->where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->update([
                    'order_commissions' => $commission
                ]);
        }
    }

    // only for customers
    public function sumSelfTurnover($userId, $month, $year, $currencyId = 1, $paidOnly = false)
    {
        $dates = $this->makeDates($month, $year);
        $orders = $this->getOrders()
            ->where('user_id', $userId)
            ->where('timestamp >=', $dates['from'])
            ->where('timestamp <=', $dates['to'])
            ->where('order_status_id != ?', OrderRepository::STATUS_STORNO);
        if($paidOnly) {
            $orders->where('isPaid', true);
        }
        return $currencyId == 2 ? $orders->sum('czprice') : $orders->sum('eurprice');
    }

    // for partners
    public function sumSelfMonthTurnover($userId, $month, $year, $currencyId = 1, $paidOnly = false)
    {
        $dates = $this->makeDates($month, $year);
        $Orders = $this->getOrders()
            ->select(':order_item.count,:order_item.freeAmount, :order_item.eurprice itemeurprice, :order_item.czprice itemczprice, :order_item.id itemid, :order_item.commissions ')
            ->joinWhere(':order_item',':order_item.order_id = orders.id')
            ->where('user_id', $userId)
            ->where('timestamp >=', $dates['from'])
            ->where('timestamp <=', $dates['to'])
            ->where('order_status_id != ?', OrderRepository::STATUS_STORNO);
        if($paidOnly) {
            $orders->where('isPaid', true);
        }
        $sum = 0;
        foreach ($Orders as $eachOrder) {
            if (count($this->array_filter_recursive(json_decode($eachOrder->commissions,true))) > 0){
                $sum += $currencyId == 2 ? $eachOrder->itemczprice * ($eachOrder->count - $eachOrder->freeAmount): $eachOrder->itemeurprice * ($eachOrder->count - $eachOrder->freeAmount);
            }
        }
        return $sum;
    }

    // only for customers
    public function myTotalTurnover($userId, $currencyId = 1)
    {
        $Orders = $this->getOrders()
            ->where('user_id', $userId)
            ->where('order_status_id != ?', OrderRepository::STATUS_STORNO);
        return $currencyId == 2 ? $Orders->sum('czprice') : $Orders->sum('eurprice');
    }

    /*
    public function sumGroupTurnover($userId, $month, $year, $currencyId = 1, $paidOnly = false)
    {
        $dates = $this->makeDates($month, $year);
        $this->customerRepository->nullSubIds();
        $group = "$userId," . $this->customerRepository->getSubIds($userId);
        $Orders = $this->getOrders()
            ->where('user_id', explode(',', $group))
            ->where('timestamp >=', $dates['from'])
            ->where('timestamp <=', $dates['to'])
            ->where('order_status_id != ?', OrderRepository::STATUS_STORNO);
        if($paidOnly) {
            $Orders->where('isPaid', true);
        }
        return $currencyId == 2 ? $Orders->sum('czprice') : $Orders->sum('eurprice');
    }
    */

    // for partners
    public function sumComGroupTurnover($userId, $month, $year, $currencyId = 1, $paidOnly = false)
    {
        $dates = $this->makeDates($month, $year);
        $this->customerRepository->nullSubIds();
        $group = "$userId," . $this->customerRepository->getSubIds($userId);
        $Orders = $this->getOrders()
            ->select(':order_item.count, :order_item.freeAmount, :order_item.eurprice itemeurprice, :order_item.czprice itemczprice, :order_item.id itemid, :order_item.commissions ')
            ->joinWhere(':order_item',':order_item.order_id = orders.id')
            ->where('user_id', explode(',', $group))
            ->where('timestamp >=', $dates['from'])
            ->where('timestamp <=', $dates['to'])
            ->where('order_status_id != ?', OrderRepository::STATUS_STORNO);
        if($paidOnly) {
            $Orders->where('isPaid', true);
        }

        $sum = 0;
        foreach ($Orders as $eachOrder) {
            if (count($this->array_filter_recursive(json_decode($eachOrder->commissions,true))) > 0){
                $sum += $currencyId == 2 ? $eachOrder->itemczprice * ($eachOrder->count - $eachOrder->freeAmount): $eachOrder->itemeurprice * ($eachOrder->count - $eachOrder->freeAmount);
            }
        }
        return $sum;
    }

    /*
    public function sumTurnoverByDate($userId, $from, $to, $currencyId = 1, $paidOnly = false, $self = false)
    {
        $this->customerRepository->nullSubIds();
        if($self == true) {
            $group = "$userId," . $this->customerRepository->getSubIds($userId);
        } else {
            $group = $userId;
        }
        $eurOrders = $this->getOrders()
            ->where('user_id', explode(',', $group))
            ->where('locale_id', 1)
            ->where('dumped', 0)
            ->where('order_status_id != ?', OrderRepository::STATUS_STORNO)
            ->where('timestamp >=', $from)
            ->where('timestamp <=', $to);
        if($paidOnly) {
            $eurOrders->where('isPaid', true);
        }
        $sum = $eurOrders->sum('price');
        $czkOrders = $this->getOrders()
            ->where('user_id', explode(',', $group))
            ->where('locale_id', 2)
            ->where('dumped', 0)
            ->where('order_status_id != ?', OrderRepository::STATUS_STORNO)
            ->where('timestamp >=', $from)
            ->where('timestamp <=', $to);
        if($paidOnly) {
            $czkOrders->where('isPaid', true);
        }

        $czkExchangeRate = $this->localeRepository->getExchangeRate(2);
        $czkOrderEur = $czkOrders->sum('price') / $czkExchangeRate;
        $sum += $czkOrderEur;
        return $currencyId == 2 ? $sum * $czkExchangeRate : $sum;
    }
    */

    // partner detail table
    public function sumTurnoverComItemsByDate($userId, $from, $to, $currencyId = 1, $paidOnly = false, $self = false)
    {
        $this->customerRepository->nullSubIds();
        if($self == true) {
            $group = "$userId," . $this->customerRepository->getSubIds($userId);
        } else {
            $group = $userId;
        }
        $Orders = $this->getOrders()
            ->select(':order_item.count, :order_item.freeAmount, :order_item.eurprice itemeurprice, :order_item.czprice itemczprice, :order_item.id itemid, :order_item.commissions ')
            ->joinWhere(':order_item',':order_item.order_id = orders.id')
            ->where('user_id', explode(',', $group))
            //->where('dumped', 0)
            ->where('order_status_id != ?', OrderRepository::STATUS_STORNO)
            ->where('timestamp >=', $from)
            ->where('timestamp <=', $to)
            ;
        if($paidOnly) {
            $Orders->where('isPaid', true);
        }

        $sum = 0;
        foreach ($Orders as $eachOrder) {
            if (count($this->array_filter_recursive(json_decode($eachOrder->commissions,true))) > 0){
                $sum += $currencyId == 2 ? $eachOrder->itemczprice * ($eachOrder->count - $eachOrder->freeAmount): $eachOrder->itemeurprice * ($eachOrder->count - $eachOrder->freeAmount);
            }
        }
        return $sum;
    }

    // partner detail table
    public function sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId = 1, $paidOnly = false, $self = false)
    {
        $this->customerRepository->nullSubIds();
        if($self == true) {
            $group = "$userId," . $this->customerRepository->getSubIds($userId);
        } else {
            $group = $userId;
        }
        $Orders = $this->getOrders()
            ->select(':order_item.count, :order_item.freeAmount, :order_item.eurprice itemeurprice, :order_item.czprice itemczprice, :order_item.id itemid, :order_item.commissions ')
            ->joinWhere(':order_item',':order_item.order_id = orders.id')
            ->where('user_id', explode(',', $group))
            //->where('dumped', 0)
            ->where('order_status_id != ?', OrderRepository::STATUS_STORNO)
            ->where('timestamp >=', $from)
            ->where('timestamp <=', $to)
            ;
        if($paidOnly) {
            $Orders->where('isPaid', true);
        }

        $sum = 0;
        foreach ($Orders as $eachOrder) {
            $sum += $currencyId == 2 ? $eachOrder->itemczprice * ($eachOrder->count - $eachOrder->freeAmount): $eachOrder->itemeurprice * ($eachOrder->count - $eachOrder->freeAmount);
        }
        return $sum;
    }

    public function getGroupTurnover($userId, $month, $year)
    {
        $turnover = $this->db->table('user_monthly_profit')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->fetch();
        return $turnover ? $turnover->group_turnover : 0;
    }

    private function makeDates($month, $year, $day = null)
    {
        $firstDay = DateTime::createFromFormat('Y-m', $year.'-'.$month);
        $firstDay->setDate($firstDay->format('Y'), $month, 1);
        $firstDay->setTime(0,0);
        $lastDay = clone $firstDay;
        $lastDay->setDate($lastDay->format('Y'), $month, $lastDay->format('t'));
        $lastDay->setTime(23, 59, 59);
        return [
            'from' => $firstDay,
            'to' => $lastDay
        ];
    }

    public function addOrderCommission($order)
    {
        $referrals = json_decode($order->referrals);
        $items = $order->related('order_item');
        $previous = $this->customerRepository->getCustomerLevel($order->user);
        $referralCommission = [];
        foreach ($referrals as $referral) {
            if($referral == 507) {
                continue;
            }
            $level = $this->customerRepository->getCustomerLevel($referral);
            if ($previous >= $level) {
                $previous = $level;
                continue;
            }
            foreach ($items as $item) {
                $commissions = json_decode($item->commissions, true);
                /*todo
                * commissions by group turnover on same user level
                */
                $commType = strpos($commissions[$level][$previous], '%') ? CommissionRepository::COMM_PERCENT : CommissionRepository::COMM_VALUE;
                $commValue = str_replace(',', '.', $commissions[$level][$previous]);
                if($commType == CommissionRepository::COMM_PERCENT) {
                    $commValue = str_replace('%', '', $commValue);
                }
                if($commValue == 0) {
                    continue;
                }
                $commission = $commType == CommissionRepository::COMM_VALUE ? $commValue * ($item->count - $item->freeAmount) : round((($commValue / 100) * $item->price) * ($item->count - $item->freeAmount), 2);
                $test = $this->db->table('user_commission')
                    ->where('user_id', $referral)
                    ->where('orders_id', $order->id)
                    ->fetch();
                if($test) {
                    $test->update([
                        'order_item_id' => $item->id,
                        'commission' => $commission,
                        'timestamp' => $order->timestamp,
//                        'type' => self::ORDER_COMMISSION
                    ]);
                } else {
                    $this->db->table('user_commission')->insert([
                        'user_id' => $referral,
                        'orders_id' => $order->id,
                        'order_item_id' => $item->id,
                        'commission' => $commission,
                        'timestamp' => $order->timestamp,
//                        'type' => self::ORDER_COMMISSION
                    ]);
                }
            }
            $referralCommission[$referral] = $commission;
            $previous = $level;
        }
        return $referralCommission;
    }

    public function getUserOrderCommissions($userId, $month, $year)
    {
        $dates = $this->makeDates($month, $year);
        $commissions = $this->db->table('user_commission')
            ->where('user_id', $userId)
            ->where('timestamp >= ?', $dates['from'])
            ->where('timestamp <= ?', $dates['to']);
        return $commissions;
    }

    public function getSumUserOrderCommissions($userId, $month, $year)
    {
        $dates = $this->getUserOrderCommissions($userId, $month, $year);
        return $dates ? $dates->sum('commission') : 0;
    }

    public function getCountPartnersToPay($month, $year)
    {
        return $this->getInMonth($month, $year)
            ->where('order_commissions > 0')
            ->count('id');
    }

    public function getSumPartnersToPay($month, $year)
    {
        $orderCommissions = $this->getInMonth($month, $year)->sum('order_commissions');
        $bonus = $this->getInMonth($month, $year)->sum('bonus');
        bdump($bonus);
        return $orderCommissions + $bonus;
    }

    public function getMonthsYears()
    {
        $out = [];
        $id = 1;
        $years = $this->getAll()->group('year')->order('year DESC');
        foreach ($years as $year) {
            $months = $this->getAll()->where('year', $year->year)->group('month')->order('month DESC')->fetchAll();
            foreach ($months as $month) {
                $out[] = [
                    'id' => $id,
                    'month' => $month->month,
                    'year' => $year->year
                ];
                $id++;
            }
        }
        return $out;
    }

    private function getOrders()
    {
        return $this->db->table('orders');
    }

    public function getPercentSumCommissionsByCurrencies($userId, $month, $year)
    {
        $eur = $this->db->table('user_commission')
            ->select('referee_id, SUM(commission) AS rsum')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('locale_id', 1)
            ->where('type', CommissionRepository::PERCENT_COMMISSION)
            ->group('referee_id')
            ->fetchPairs('referee_id', 'rsum');
        $czk = $this->db->table('user_commission')
            ->select('referee_id, SUM(commission) AS rsum')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('locale_id', 2)
            ->where('type', CommissionRepository::PERCENT_COMMISSION)
            ->group('referee_id')
            ->fetchPairs('referee_id', 'rsum');
        $out = [];
        foreach ($eur as $eurkey => $euritem) {
            $out[$eurkey] = ['czk' => 0, 'eur' => $euritem];
        }
        foreach ($czk as $czkkey => $czkitem) {
            $out[$czkkey] = ['czk' => $czkitem, 'eur' => $out[$czkkey]['eur'] ?? 0];
        }
        return $out;
    }

    public function getProductSumCommissionsByCurrencies($userId, $month, $year)
    {
        $eur = $this->db->table('user_commission')
            ->select('referee_id, SUM(commission) AS rsum')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('locale_id', 1)
            ->where('type', CommissionRepository::PERCENT_COMMISSION)
            ->group('referee_id')
            ->fetchPairs('referee_id', 'rsum');
        $czk = $this->db->table('user_commission')
            ->select('referee_id, SUM(commission) AS rsum')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('locale_id', 2)
            ->where('type', CommissionRepository::PERCENT_COMMISSION)
            ->group('referee_id')
            ->fetchPairs('referee_id', 'rsum');
        $out = [];
        foreach ($eur as $eurkey => $euritem) {
            $out[$eurkey] = ['czk' => 0, 'eur' => $euritem];
        }
        foreach ($czk as $czkkey => $czkitem) {
            $out[$czkkey] = ['czk' => $czkitem, 'eur' => $out[$czkkey]['eur'] ?? 0];
        }
        return $out;
    }

    public function sumAllMonthlyCommissions($month, $year, $withVat = false, $userId = null)
    {
        $eurcomnovat = $this->db->table('user_commission')
            ->where('month', $month)
            ->where('year', $year)
            ->where('locale_id', 1);
        if($userId) {
            $eurcomnovat->where('user_id', $userId);
        }
        $eurcomnovat = $eurcomnovat->sum('commission');
        $czkcomnovat = $this->db->table('user_commission')
            ->where('month', $month)
            ->where('year', $year)
            ->where('locale_id', 2);
        if($userId) {
            $czkcomnovat->where('user_id', $userId);
        }
        $czkcomnovat = $czkcomnovat->sum('commission');
        if($withVat) {
            $eurcomnovat = $eurcomnovat * 1.2;
            $czkcomnovat = $czkcomnovat * 1.15;
            $eurcom = $eurcomnovat;
            $czkcom = $czkcomnovat;
            $czkExchangeRate = $this->localeRepository->getExchangeRate(2);
            $czkcom = $czkcom / $czkExchangeRate;
            return round($czkcom + $eurcom, 2);
        } else {
            $eurcom = $eurcomnovat;
            $czkcom = $czkcomnovat;
            $czkExchangeRate = $this->localeRepository->getExchangeRate(2);
            $czkcom = $czkcom / $czkExchangeRate;
            return round($czkcom + $eurcom, 2);
        }
    }

    public function sumAllBonuses($month, $year, $withVat = false, $userId = null)
    {
        $eurcomnovat = $this->db->table('monthly_run')
            ->where('month', $month)
            ->where('year', $year)
            ->where('locale_id', 1);
        if($userId) {
            $eurcomnovat->where('user_id', $userId);
        }
        $eurcomnovat = $eurcomnovat->sum('bonus');
        $czkcomnovat = $this->db->table('monthly_run')
            ->where('month', $month)
            ->where('year', $year)
            ->where('locale_id', 2);
        if($userId) {
            $czkcomnovat->where('user_id', $userId);
        }
        $czkcomnovat = $czkcomnovat->sum('bonus');
        if($withVat) {
            $eurcomnovat = $eurcomnovat * 1.2;
            $czkcomnovat = $czkcomnovat * 1.15;
            $eurcom = $eurcomnovat;
            $czkcom = $czkcomnovat;
            $czkExchangeRate = $this->localeRepository->getExchangeRate(2);
            $czkcom = $czkcom / $czkExchangeRate;
            return round($czkcom + $eurcom, 2);
        } else {
            $eurcom = $eurcomnovat;
            $czkcom = $czkcomnovat;
            $czkExchangeRate = $this->localeRepository->getExchangeRate(2);
            $czkcom = $czkcom / $czkExchangeRate;
            return round($czkcom + $eurcom, 2);
        }
    }

    public function countMonthlyComissions($month, $year)
    {
        $sum = $this->db->table('user_commission')
            ->where('month', $month)
            ->where('year', $year)
            ->group('user_id');
        return $sum ? $sum->count() : null;
    }

    public function getPeriods()
    {
        $out = [];
        $years = $this->db->table('monthly_run')->select('*')->group('year')->fetchAll();
        foreach ($years as $year) {
            $months = $this->db->table('monthly_run')->select('*')->group('month')->fetchAll();
            foreach ($months as $month) {
                $from = DateTime::createFromFormat('d.m.Y', '01.' . $month->month . '.' . $year->year);
                $to = DateTime::createFromFormat('d.m.Y', '01.' . $month->month . '.' . $year->year);
                $sum = $this->sumAllMonthlyCommissions($month->month, $year->year);
                $bonus = $this->sumAllBonuses($month->month, $year->year);
                $count = $this->countMonthlyComissions($month->month, $year->year);
                if($sum == 0) {
                    continue;
                }
                $to->modify('last day of');
                $out[] = [
                    'date' => $from->format('d.m.Y').' - '.$to->format('d.m.Y'),
                    'count' => $count,
                    'sum' => $sum + $bonus,
                    'month' => $month->month,
                    'year' => $year->year
                ];
            }
        }
        return $out;
    }

    public function getUsersMonthlyOverview($month, $year)
    {
        $commissions = $this->db->table('monthly_run')
            ->where('month', $month)
            ->where('year', $year);
        $out = [];
        $a = 1;
        foreach ($commissions as $comms) {
            $comm = $this->sumAllMonthlyCommissions($month, $year, false, $comms->user_id);
            $bonus = $this->sumAllBonuses($month, $year, false, $comms->user_id);
            if($comm == 0) {
                continue;
            }
            $out[] = [
                'id' => $comms->user_id,
                'ref_no' => $comms->user->ref_no,
                'partner' => $comms->user->firstName . ' ' . $comms->user->lastName,
                'level' => $comms->user_level->name,
                'commission' => $comm,
                'turnover' => round($comms->turnover, 2),
                'bonus' => $comms->bonus,
                'sum' => $bonus + $comm
            ];
            $a++;
        }
        return $out;
    }

    public function getExchangeRate($localeId = 2)
    {
        return $this->localeRepository->getExchangeRate($localeId);
    }


    public function array_filter_recursive($array) {
        foreach ($array as $key => &$value) {
            if (empty($value) || $value == []) {
                unset($array[$key]);
            }
            else {
                if (is_array($value)) {
                $value = $this->array_filter_recursive($value);
                if (empty($value) || $value == []) {
                    unset($array[$key]);
                }
                }
            }
        }
        return $array;
    }

}