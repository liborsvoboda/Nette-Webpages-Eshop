<?php

namespace App\AdminModule\Components\Commission;

use App\Model\Commission\MonthlyCommissionRepository;
use App\Model\Factory\GridFactory;
use Nette\Application\UI\Control;
use Nette\Utils\DateTime;

class MonthlyOverviewCommissionGrid extends Control
{
    private MonthlyCommissionRepository $monthlyCommissionRepository;
    private GridFactory $gridFactory;
    public array $onDetail = [];

    public function __construct(MonthlyCommissionRepository $monthlyCommissionRepository, GridFactory $gridFactory)
    {
        $this->monthlyCommissionRepository = $monthlyCommissionRepository;
        $this->gridFactory = $gridFactory;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('month', 'Obdobie')
            ->setRenderer(function ($row){
                $date = DateTime::createFromFormat('Y-m', $row['year'] . '-' . $row['month']);
                return '01.'.$date->format('m.Y').' - '.$date->format('t. m. Y');
            });
        $grid->addColumnText('partners', 'Počet partnerov na vyplatenie')
            ->setRenderer(function ($row) {
                return $this->monthlyCommissionRepository->getCountPartnersToPay($row['month'], $row['year']);
            });
        $grid->addColumnText('sum', 'Provízie celkom')
            ->setRenderer(function ($row) {
                return $this->monthlyCommissionRepository->getSumPartnersToPay($row['month'], $row['year']);
            });
        $grid->addAction('detail', 'Detail', 'detail');
        return $grid;
    }

    public function handleDetail($id)
    {
        $row = $this->monthlyCommissionRepository->getById($id)->fetch();
        $this->onDetail($row->month, $row->year);
    }

    private function getDataSource()
    {
        return $this->monthlyCommissionRepository->getMonthsYears();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/monthlyOverviewCommissionGrid.latte');
    }
}