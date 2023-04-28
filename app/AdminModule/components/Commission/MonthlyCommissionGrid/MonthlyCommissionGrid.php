<?php

namespace App\AdminModule\Components\Commission;

use App\Model\Commission\MonthlyCommissionRepository;
use App\Model\Factory\GridFactory;
use Nette\Application\UI\Control;

class MonthlyCommissionGrid extends Control
{
    private MonthlyCommissionRepository $monthlyCommissionRepository;
    private GridFactory $gridFactory;
    private int $month, $year;
    public $onGetPdf = [];

    public function __construct(MonthlyCommissionRepository $monthlyCommissionRepository, GridFactory $gridFactory)
    {
        $this->monthlyCommissionRepository = $monthlyCommissionRepository;
        $this->gridFactory = $gridFactory;
    }

    public function setDate($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('ref_no', 'Ref č');
        $grid->addColumnText('partner', 'Partner');
        $grid->addColumnText('level', 'Pozícia');
        $grid->addColumnText('turnover', 'Obrat');
        $grid->addColumnText('commission', 'Provízia');
        $grid->addColumnNumber('bonus', 'Motivačný bonus');
        $grid->addColumnText('sum', 'Celková provízia');
        $grid->addAction('pdf', 'Mesačný výpis', 'getPdf', ['id']);
        return $grid;
    }

    public function handleGetPdf($id)
    {
        $this->onGetPdf($id, $this->month, $this->year);
    }

    private function getDataSource()
    {
        return $this->monthlyCommissionRepository->getUsersMonthlyOverview($this->month, $this->year);
    }

    public function render()
    {
        $this->template->month = $this->month;
        $this->template->year = $this->year;
        $this->template->render(__DIR__.'/templates/monthlyCommissionGrid.latte');
    }
}