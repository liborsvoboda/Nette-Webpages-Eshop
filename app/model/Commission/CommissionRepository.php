<?php


namespace App\Model\Commission;


use App\Model\BaseRepository;
use App\Model\Customer\CustomerRepository;
use App\Model\Order\OrderRepository;
use App\Model\Services\UserManager;
use App\Model\User\UserRepository;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Database\Context;
use Nette\Utils\DateTime;

class CommissionRepository extends BaseRepository
{

    private $customerRepository, $db, $table = 'user_commission';

    CONST PRODUCT_COMMISSION = 1,
        PERCENT_COMMISSION = 2,
        BONUS = 3;

    const COMM_VALUE = 1, COMM_PERCENT = 2;
    private MonthlyCommissionRepository $monthlyCommissionRepository;
    private UserLevelRepository $userLevelRepository;
    private UserRepository $userRepository;

    public function __construct(CustomerRepository $customerRepository,
                                Context $db,
                                MonthlyCommissionRepository $monthlyCommissionRepository,
                                UserLevelRepository $userLevelRepository,
                                UserRepository $userRepository)
    {
        $this->customerRepository = $customerRepository;
        $this->db = $db;
        $this->monthlyCommissionRepository = $monthlyCommissionRepository;
        $this->userLevelRepository = $userLevelRepository;
        $this->userRepository = $userRepository;
    }

    public function getCommission($userId, $fromDate, $toDate)
    {
        $commissions = $this->db->table($this->table)
            ->where('user_id', $userId)
            ->where('timestamp >= ?', $fromDate)
            ->where('timestamp <= ?', $toDate);
        return $commissions;
    }

    public function getBonus($userId, $fromDate, $toDate)
    {
        return 0;
    }

    public function getAllCommissions($fromDate, $toDate)
    {
        $commissions = $this->db->table($this->table)
            ->where('user_commission.timestamp >= ?', $fromDate)
            ->where('user_commission.timestamp <= ?', $toDate);
        return $commissions;
    }

    public function getAllCommissionsForUser($fromDate, $toDate, $userId)
    {
        $commissions = $this->db->table($this->table)
            ->where('user_commission.user_id', $userId)
            ->where('user_commission.timestamp >= ?', $fromDate)
            ->where('user_commission.timestamp <= ?', $toDate);
        return $commissions;
    }

    public function addDirectReferee($month, $year)
    {
        $commissions = $this->db->table($this->table)
            ->where('month' , $month)
            ->where('year', $year);
        foreach ($commissions as $commission) {
            $userId = $commission->user_id;
            $referrals = json_decode($commission->orders->referrals, true);
            dump($userId);
            $xx = array_search($userId, $referrals);
            if($xx >0) {
                $refId = $referrals[$xx-1];
            } else {
                $refId = $commission->referee;
            }
            $commission->update(['direct_referee_id' => $refId]);
        }
        die;
    }

    public function addCommissions($order)
    {
        $referrals = json_decode($order->referrals);
        $items = $order->related('order_item');
        $previous = $this->customerRepository->getCustomerLevel($order->user);
        $previousId = $order->user_id;
        $customer = $this->customerRepository->getCustomerLevel($order->user);
        foreach ($referrals as $referral) {
//            if($referral == 507) {
//                continue;
//            }
            $level = $this->customerRepository->getCustomerLevel($referral);
            //dump($level);
            if ($previous >= $level) {
                $previous = $level;
                $previousId = $referral;
                continue;
            }
            foreach ($items as $item) {
                $coms = $item->product->related('product_lang')->where('lang_id', $order->locale_id)->fetch();
                //$commissions = json_decode($coms->commissions, true);
                $commissions = json_decode($item->commissions, true);
                //$commissions = $this->commissionTable();
                $commType = strpos($commissions[$level][$previous], '%') ? self::COMM_PERCENT : self::COMM_VALUE;
                $commValue = str_replace(',', '.', $commissions[$level][$previous]);
                dump($level);dump($previous);
                if($commType == self::COMM_PERCENT) {
                    $commValue = str_replace(',', '.', $commissions[$level][$previous]);
                    dump($commValue);
                    $commValue = str_replace('%', '', $commValue);
                }
                if($commValue == 0) {
                    continue;
                }
                $commission = $commType == self::COMM_VALUE ? $commValue * $item->count : round((($commValue / 100) * $item->price) * $item->count, 2);
                dump($commission);
                $this->db->table($this->table)->insert([
                    'user_id' => $referral,
                    'orders_id' => $order->id,
                    'order_item_id' => $item->id,
                    'commission' => $commission,
                    'timestamp' => new DateTime(),
                    'referee_id' => $previousId
                ]);
            }
            $previous = $level;
            $previousId = $referral;
        }
    }

    public function addPercentageCommissions($order, $month, $year)
    {
        $referrals = json_decode($order->referrals);
        $items = $order->related('order_item');
        $previous = $this->getCustomerLevel($order->user, $month, $year);
        $previousId = $order->user_id;
        $customer = $this->getCustomerLevel($order->user, $month, $year);
        foreach ($referrals as $referral) {
           /* if($referral == 507 || $referral == 514 ) {
                continue;
            }*/
            $level = $this->getCustomerLevel($referral, $month, $year);
//            dump($previousId);
            $previous = $previous < 5 ? 5 : $previous;
            if ($previous >= $level) {
                $previous = $level;
                continue;
            }
            foreach ($items as $item) {
                $coms = $item->product->related('product_lang')->where('lang_id', $order->locale_id)->fetch();
                $commissions = json_decode($coms->commissions, true);

                //$commissions = json_decode($item->commissions, true);
                //$commissions = $this->commissionTable();
//                dump($previous);dump($level);
                $commType = strpos($commissions[$level][$previous], '%') ? self::COMM_PERCENT : self::COMM_VALUE;
                $commValue = str_replace(',', '.', $commissions[$level][$previous]);
//                dump($level);dump($previous);
//                dump($commValue);dump($level);dump($previous);
                if($commType == self::COMM_VALUE) {
                    continue;
                }
                if($commType == self::COMM_PERCENT) {
                    $commValue = str_replace(',', '.', $commissions[$level][$previous]);
                    //dump($commValue);
                    $commValue = str_replace('%', '', $commValue);
                }
                if($commValue == 0) {
                    continue;
                }
                $vat = $item->product->related('product_price')->where('locale_id', $order->locale_id)->fetch()->vat;
                $itemNoVat = $item->price / (($vat + 100) / 100);
                $commission = round((($commValue / 100) * $itemNoVat) * $item->count, 2);
//                dump($commission);
//            dump($previousId);
                $this->db->table($this->table)->insert([
                    'user_id' => $referral,
                    'orders_id' => $order->id,
                    'order_item_id' => $item->id,
                    'commission' => $commission,
                    'timestamp' => $order->timestamp,
                    'referee_id' => $previousId,
                    'type' => self::PERCENT_COMMISSION,
                    'item_count' => $item->count,
                    'locale_id' => $order->locale_id,
                    'month' => $month,
                    'year' => $year,
                    'percent' => $commValue,
                    'item_price' => $item->price * $item->count
                ]);
            }
            $previous = $level;
            $previousId = $referral;
        }
    }

    public function addProductCommissions($order, $month, $year)
    {
        $referrals = json_decode($order->referrals);
        $items = $order->related('order_item');
        $previous = $this->getCustomerLevel($order->user, $month, $year);
        $previousId = $order->user_id;
        $customer = $this->getCustomerLevel($order->user, $month, $year);
        foreach ($referrals as $referral) {
           /* if($referral == 507 || $referral == 514) {
                continue;
            }*/
            $level = $this->getCustomerLevel($referral, $month, $year);
            bdump($level);
//            dump($previous);
//          dump($level);
//          dump($level);dump($previous);
            if ($previous >= $level) {
//                $previous = $level;
                continue;
            }
            foreach ($items as $item) {
                $coms = $item->product->related('product_lang')->where('lang_id', $order->locale_id)->fetch();
                $commissions = json_decode($coms->commissions, true);
//                $commissions = json_decode($item->commissions, true);
                $commType = strpos($commissions[$level][$previous], '%') ? self::COMM_PERCENT : self::COMM_VALUE;
                $commValue = str_replace(',', '.', $commissions[$level][$previous]);
//                dump($commValue);
                if($commType == self::COMM_PERCENT) {
                    continue;
                }
                if($commValue == 0) {
                    continue;
                }
                $commission = $commValue * $item->count;
//                dump($commission);
                $this->db->table($this->table)->insert([
                    'user_id' => $referral,
                    'orders_id' => $order->id,
                    'order_item_id' => $item->id,
                    'commission' => $commission,
                    'timestamp' => $order->timestamp,
                    'referee_id' => $previousId,
                    'type' => self::PRODUCT_COMMISSION,
                    'item_count' => $item->count,
                    'locale_id' => $order->locale_id,
                    'month' => $month,
                    'year' => $year,
                    'item_price' => $item->price * $item->count
                ]);
            }
            $previous = $level;
            $previousId = $referral;
        }
    }

    public function commissionTable($asJson = false)
    {
        $commissions = [
            1 => [],
            2 => [],
            3 => [2 => '2,5'],
            4 => [2 => '5', 3 => '2,5'],
            5 => [2 => '7,5', 3 => '5', 4 => '2,5'],
            6 => [2 => '7,5', 3 => '5', 4 => '2,5', 5 => '5%'],
            7 => [2 => '7,5', 3 => '5', 4 => '2,5', 5 => '8%', 6 => '3%'],
            8 => [2 => '7,5', 3 => '5', 4 => '2,5', 5 => '10%', 6 => '5%', 7 => '2%'],
            9 => [2 => '7,5', 3 => '5', 4 => '2,5', 5 => '12%', 6 => '7%', 7 => '4%', 8 => '2%'],
            10 => [2 => '7,5', 3 => '5', 4 => '2,5', 5 => '12%', 6 => '7%', 7 => '4%', 8 => '3%', 9 => '2%'],
            11 => [2 => '7,5', 3 => '5', 4 => '2,5', 5 => '12%', 6 => '7%', 7 => '4%', 8 => '4%', 9 => '3%', 10 => '2%'],
        ];
        return $asJson ? json_encode($commissions) : $commissions;
    }

    public function changeUsersLevel($month, $year)
    {
        $levels = $this->userLevelRepository->getAll();
        $users = $this->userRepository->getAll()->where('role != ?', UserManager::USER_ADMIN);
        foreach ($users as $user) {
            $userId = $user->id;
            $turnover = $this->monthlyCommissionRepository->getGroupTurnover($userId);
            $turnover = $this->monthlyCommissionRepository->sumComGroupTurnover($userId, $month, $year);
            $level = $this->userLevelRepository->getNewLevelByTurnover($turnover);
            /*
            if($level) {
                $this->userRepository->setNewUserLevel($userId, 1);
            }
            */
        }
    }

    private function getCustomerLevel($userId, $month, $year)
    {
        $monRun = $this->db->table('monthly_run')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->fetch();

        if($monRun) {
            $custLevel = $this->customerRepository->getCustomerLevel($userId);
            if($custLevel == 3 && $custLevel > $monRun->user_level_id) {
                return 3;
            }
            return $monRun->user_level_id == 1 ? 2 : $monRun->user_level_id;
        } else {
            return $this->customerRepository->getCustomerLevel($userId);
        }
    }

}