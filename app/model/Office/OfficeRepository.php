<?php

namespace App\Model\Office;

use App\Model\BaseRepository;
use App\Model\LocaleRepository;
use App\Model\Commission\MonthlyCommissionRepository;
use Nette\Utils\DateTime;

class OfficeRepository extends BaseRepository
{
    private MonthlyCommissionRepository $monthlyCommissionRepository;
    private LocaleRepository $localeRepository;

    public function __construct(MonthlyCommissionRepository $monthlyCommissionRepository, LocaleRepository $localeRepository)
    {
        $this->monthlyCommissionRepository = $monthlyCommissionRepository;
        $this->localeRepository = $localeRepository;
      
    }

    public function getTurnovers($userId)
    {
        $currencyId = $this->localeRepository->getCurrencyId();
        $from = new DateTime();
        $to = new DateTime();
        $from->setTime(0, 0);
        $to->setTime(23, 59, 59);
        $tgTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, false, true);
        $tsTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, false, false);
        $tgAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, false, true);
        $tsAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, false, false);
        $from->sub(new \DateInterval('P1D'));
        $to->sub(new \DateInterval('P1D'));
        $ygTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, false, true);
        $ytTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, false, false);
        $ygAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, false, true);
        $ytAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, false, false);
        $from->setDate($from->format('Y'), $from->format('m'), 1);
        $to->setDate($to->format('Y'), $to->format('m'), $to->format('t'));
        $tmgTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, false, true);
        $tmsTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, false, false);
        $tmgAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, false, true);
        $tmsAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, false, false);
        $from->modify('first day of previous month');
        $to->modify('last day of previous month');
        $pmgTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, true);
        $pmsTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, false);
        $pmgAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, true);
        $pmsAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, false);
        $from->modify('-2 months');
        $to = new DateTime();
        $thmgTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, true);
        $thmsTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, false);
        $thmgAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, true);
        $thmsAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, false);
        $from->modify('-3 months');
        $smgTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, true);
        $smsTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, false);
        $smgAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, true);
        $smsAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, false);
        $from->setDate($to->format('Y'), 1, 1);
        $to->setDate($from->format('Y'), 12, 31);
        $to->setTime(23, 59, 59);
        $tygTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, true);
        $tysTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, false);
        $tygAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, true);
        $tysAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, false);
        $from->setDate($to->format('Y'), 1, 1);
        $from->sub(new \DateInterval('P1Y'));
        $to->setDate($from->format('Y'), 12, 31);
        $to->setTime(23, 59, 59);
        $lygTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, true);
        $lysTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, false);
        $lygAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, true);
        $lysAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, false);
        $from->setTimestamp(0);
        $to = new DateTime();
        $agTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, true);
        $asTurn = $this->monthlyCommissionRepository->sumTurnoverComItemsByDate($userId, $from, $to, $currencyId, true, false);
        $agAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, true);
        $asAllTurn = $this->monthlyCommissionRepository->sumTurnoverAllItemsByDate($userId, $from, $to, $currencyId, true, false);
        $out = [
            'gtToday' => $tgTurn,
            'stToday' => $tsTurn,
            'gtYesterday' => $ygTurn,
            'stYesterday' => $ytTurn,
            'gtThisMonth' => $tmgTurn,
            'stThisMonth' => $tmsTurn,
            'gtPrevMonth' => $pmgTurn,
            'stPrevMonth' => $pmsTurn,
            'gtThreeMonth' => $thmgTurn,
            'stThreeMonth' => $thmsTurn,
            'gtSixMonth' => $smgTurn,
            'stSixMonth' => $smsTurn,
            'gtThisYear' => $tygTurn,
            'stThisYear' => $tysTurn,
            'gtLastYear' => $lygTurn,
            'stLastYear' => $lysTurn,
            'gtAll' => $agTurn,
            'stAll' => $asTurn,

            'gtAllToday' => $tgAllTurn,
            'stAllToday' => $tsAllTurn,
            'gtAllYesterday' => $ygAllTurn,
            'stAllYesterday' => $ytAllTurn,
            'gtAllThisMonth' => $tmgAllTurn,
            'stAllThisMonth' => $tmsAllTurn,
            'gtAllPrevMonth' => $pmgAllTurn,
            'stAllPrevMonth' => $pmsAllTurn,
            'gtAllThreeMonth' => $thmgAllTurn,
            'stAllThreeMonth' => $thmsAllTurn,
            'gtAllSixMonth' => $smgAllTurn,
            'stAllSixMonth' => $smsAllTurn,
            'gtAllThisYear' => $tygAllTurn,
            'stAllThisYear' => $tysAllTurn,
            'gtAllLastYear' => $lygAllTurn,
            'stAllLastYear' => $lysAllTurn,
            'gtAllAll' => $agAllTurn,
            'stAllAll' => $asAllTurn,
            'currencyId' => $currencyId,
        ];
        return $out;
    }
}