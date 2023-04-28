<?php

namespace App\FrontModule\Components\Office;

use App\Model\Commission\MonthlyCommissionRepository;
use App\Model\Factory\GridFactory;
use App\Model\User\UserRepository;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\UI\Control;

class PartnerGrid extends Control
{
    private GridFactory $gridFactory;
    private UserRepository $userRepository;
    private $userId;
    public $onDetail = [];
    private UserLevelRepository $userLevelRepository;
    private MonthlyCommissionRepository $monthlyCommissionRepository;


    public function __construct(GridFactory $gridFactory, UserRepository $userRepository, UserLevelRepository $userLevelRepository, MonthlyCommissionRepository $monthlyCommissionRepository)
    {
        $this->gridFactory = $gridFactory;
        $this->userRepository = $userRepository;
        $this->userLevelRepository = $userLevelRepository;
        $this->monthlyCommissionRepository = $monthlyCommissionRepository;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function createComponentGrid()
    {
        $levels = $this->userLevelRepository->getForSelect();
        $month = date('m');
        $year = date('Y');
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('ref_no', 'Ref. číslo');
        $grid->addColumnText('uname', 'Meno, mesto');
        $grid->addColumnText('user_level_id', 'Kariérny stupeň')
            ->setRenderer(function ($row) use ($levels) {
                return $levels[$row->user_level_id];
            });
        $grid->addColumnText('turnover', 'Provizný Obrat')
            ->setRenderer(function ($row) use ($month, $year) {
                return number_format($this->monthlyCommissionRepository->sumComGroupTurnover($row->id, $month, $year),2,',','');
            });
        $grid->addAction('detail', 'Detail', 'detail')
            ->setClass('');
        $grid->setDefaultPerPage(20);
        $grid->addFilterText('search', 'Hladanie', ['firstName', 'lastName']);
        $grid->setOuterFilterRendering(true);
        $grid->setCollapsibleOuterFilters(false);
        $grid->setRememberState(false);
        return $grid;
    }

    public function handleDetail($id)
    {
        $this->onDetail($id);
    }

    private function getDataSource()
    {
        return $this->userRepository->getDirectReferees($this->userId)
            ->select('*, CONCAT(firstName, " ", lastName, ", ", city) uname');
    }

    public function render()
    {
        $this->template->render(__DIR__.'/partnerGrid.latte');
    }
}