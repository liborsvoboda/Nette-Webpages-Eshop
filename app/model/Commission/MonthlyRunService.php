<?php

namespace App\Model\Commission;

use App\Model\Order\OrderRepository;
use App\Model\Services\PdfService;
use App\Model\Services\UserManager;
use App\Model\User\UserRepository;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Database\Context;
use Nette\Utils\DateTime;

class MonthlyRunService
{
    private OrderRepository $orderRepository;
    private Context $db;
    private UserRepository $userRepository;
    private MonthlyCommissionRepository $monthlyCommissionRepository;
    private int $month;
    private int $year;
    private UserLevelRepository $userLevelRepository;
    private CommissionRepository $commissionRepository;
    private PdfService $pdfService;

    public function __construct(OrderRepository $orderRepository,
                                Context $db,
                                UserRepository $userRepository,
                                MonthlyCommissionRepository $monthlyCommissionRepository,
                                UserLevelRepository $userLevelRepository,
                                CommissionRepository $commissionRepository,
                                PdfService $pdfService)
    {
        $this->orderRepository = $orderRepository;
        $this->db = $db;
        $this->userRepository = $userRepository;
        $this->monthlyCommissionRepository = $monthlyCommissionRepository;
        $this->userLevelRepository = $userLevelRepository;
        $this->commissionRepository = $commissionRepository;
        $this->pdfService = $pdfService;
    }

    public function setMonth($month)
    {
        $this->month = $month;
    }

    public function setYear($year)
    {
        $this->year = $year;
    }

    public function saveTurnovers()
    {
        $users = $this->userRepository->getAll();//->where('role', UserManager::USER_CUSTOMER);
        foreach ($users as $user) {
            $turnover = $this->monthlyCommissionRepository->sumComGroupTurnover($user->id, $this->month, $this->year, 1, true);
            
            if($turnover > 0) {
                $level = $this->userLevelRepository->getLevelByTurnover($turnover);
                $this->db->table('monthly_run')->insert([
                    'user_id' => $user->id,
                    'month' => $this->month,
                    'year' => $this->year,
                    'turnover' => $turnover,
                    'user_level_id' => $level == 1 ? 2 : $level
                ]);
            }
        }
    }

    public function saveCommissions()
    {
        $from = DateTime::createFromFormat('d.m.Y', '01.'. $this->month . '.' . $this->year);
        $to = DateTime::createFromFormat('d.m.Y', '01.'. ($this->month + 1) . '.' . $this->year);
        $from->setTime(0,0,0);
        $to->setTime(0,0,0);
        $orders = $this->orderRepository->getAll()
            ->where('timestamp >= ?', $from)
            ->where('timestamp <= ?', $to)
            ->where('isPaid', true)
            ->where('user_id IS NOT NULL');
        foreach ($orders as $order) {
            $this->commissionRepository->addProductCommissions($order, $this->month, $this->year);
            $this->commissionRepository->addPercentageCommissions($order, $this->month, $this->year);
        }
    }

    public function saveBonus()
    {
        $users = $this->db->table('monthly_run')
            ->where('month', $this->month)
            ->where('year', $this->year);
        $bonuses = $this->userLevelRepository->getBonuses();
        foreach ($users as $user) {
            $bonus = $bonuses[$user->user_level_id][$user->user->country_id] ?? 0;
            $user->update(['bonus' => $bonus, 'locale_id' => $user->user->country_id]);
        }
    }

    public function makePdf($userId, $month, $year)
    {
        $run = $this->db->table('monthly_run')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->fetch();
        $exchangeRate = $this->monthlyCommissionRepository->getExchangeRate(2);
        $turnover = $run->user->country_id == 2 ? round($run->turnover * $exchangeRate, 2) : round($run->turnover, 2);
        $product_commissions = $this->getProductCommissions($userId, $month, $year, $run->user->country_id);
        $percent_commissions = $this->getPercentCommissions($userId, $month, $year, $run->user->country_id);
        $sumProduct = 0;
        $sumPercent = 0;
        foreach ($product_commissions as $product_commission) {
            $sumProduct += $product_commission['commission'];
        }
        foreach ($percent_commissions as $percent_commission) {
            $sumPercent += $percent_commission['commission'];
        }
        $sumComm = $sumProduct + $sumPercent;
        $data = [
            'user' => [
                'id' => $userId,
                'name' => $run->user->firstName.' '.$run->user->lastName,
                'companyName' => $run->user->companyName,
                'ref_no' => $run->user->ref_no,
                'isCompany' => $run->user->isCompany,
                'street' => $run->user->street,
                'city' => $run->user->city,
                'zip' => $run->user->zip,
                'countryCode' => strtoupper($run->user->country->code),
                'ico' => $run->user->ico,
                'dic' => $run->user->dic,
                'icdph' => $run->user->icdph,
                'level' => $run->user_level->name
            ],
            'sum_turnover' => $turnover,
            'locale_id' => $run->user->country->code,
            'product_commissions' => $product_commissions,
            'percent_commissions' => $percent_commissions,
            'sum_product' => $sumProduct,
            'sum_percent' => $sumPercent,
            'summ_comm' => $sumComm,
            'bonus' => $run->bonus
        ];
        $locale = str_replace('cz', 'cs', $run->user->country->code);
        $pdf = $this->pdfService->makeCommissionList($data, $month, $year, $locale);
        return $pdf;
    }

    public function makeMonthlyRun($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
        $this->cleanDb();
        $this->saveTurnovers();
        $this->saveCommissions();
        $this->saveBonus();
        $this->commissionRepository->addDirectReferee($this->month, $this->year);
    }

    public function cleanDb()
    {
        $this->db->table('monthly_run')->where('month', $this->month)->where('year', $this->year)->delete();
        $this->db->table('user_commission')->where('month', $this->month)->where('year', $this->year)->delete();
    }

    private function getProductCommissions($userId, $month, $year, $localeId)
    {
        $out = [];
        $comms = $this->db->table('user_commission')
            ->where('user_id', $userId)
            ->where('type', CommissionRepository::PRODUCT_COMMISSION)
            ->where('month', $month)
            ->where('year', $year);
        foreach ($comms as $comm) {
            if(!isset($out[$comm->direct_referee_id])) {
                $out[$comm->direct_referee_id] = [
                    'commission' => 0,
                    'turnover' => 0
                ];
            }
            $out[$comm->direct_referee_id]['commission'] += $this->getLocaleValue($localeId, $comm->locale_id, $comm->commission);
            $out[$comm->direct_referee_id]['turnover'] += $this->getLocaleValue($localeId, $comm->locale_id, $comm->item_price);
        }
        $referrees = $this->userRepository->getDirectReferees($userId);
        foreach ($referrees as $referee) {
            if(isset($out[$referee->id])) {
                continue;
            } else {
                $turnover = $this->monthlyCommissionRepository->sumComGroupTurnover($referee->id, $month, $year, $localeId);//$referee->country_id);
                $out[$referee->id] = [
                    'commission' => 0,
                    'turnover' => $turnover
                ];
            }
        }
        ksort($out);
        return $out;
    }

    private function getPercentCommissions($userId, $month, $year, $localeId)
    {
        $out = [];
        $comms = $this->db->table('user_commission')
            ->where('user_id', $userId)
            ->where('type', CommissionRepository::PERCENT_COMMISSION)
            ->where('month', $month)
            ->where('year', $year);
        foreach ($comms as $comm) {
            if(!isset($out[$comm->direct_referee_id])) {
                $out[$comm->direct_referee_id] = [
                    'commission' => 0,
                    'turnover' => 0
                ];
            }
            $out[$comm->direct_referee_id]['commission'] += $this->getLocaleValue($localeId, $comm->locale_id, $comm->commission);
            $out[$comm->direct_referee_id]['turnover'] = $comm->percent;
        }
        return $out;
    }

    private function getLocaleValue($userLocaleId, $orderLocaleId, $value)
    {
        if($userLocaleId == $orderLocaleId) {
            return round($value, 2);
        }
        $exchangeRate = $this->monthlyCommissionRepository->getExchangeRate(2);

        if($userLocaleId == 1 && $orderLocaleId == 2) {
            return round($value / $exchangeRate, 2);
        }
        if($userLocaleId == 2 && $orderLocaleId == 1) {
            return round($value * $exchangeRate, 2);
        }
    }

    private function saveSelfCommissions($month, $year)
    {

    }

    public function getMonthlyTurnover($userId)
    {
        $turnovers = $this->db->table('monthly_run')->where('user_id', $userId)->order('id DESC');
        return $turnovers;
    }

}