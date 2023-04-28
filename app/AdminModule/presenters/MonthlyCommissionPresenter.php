<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\Commission\IMonthlyCommissionGridFactory;
use App\AdminModule\Components\Commission\IMonthlyOverviewCommissionGridFactory;
use App\Model\Commission\MonthlyCommissionRepository;
use App\Model\Commission\MonthlyRunService;

class MonthlyCommissionPresenter extends OverviewPresenter
{

    /**
     * @var IMonthlyOverviewCommissionGridFactory
     * @inject
     */
    public $monthlyOverviewCommissionGrid;

    /**
     * @var IMonthlyCommissionGridFactory
     * @inject
     */
    public $monthlyCommisssionGrid;

    /**
     * @var MonthlyCommissionRepository
     * @inject
     */
    public $monthlyCommissionRepository;

    /**@persistent */
    public int $month;

    /**@persistent */
    public int $year;

    /**
     * @var MonthlyRunService
     * @inject
     */
    public $monthlyRunService;

    public function actionDefault()
    {
        $this->template->periods = $this->monthlyCommissionRepository->getPeriods();
    }

    public function actionMonth($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function createComponentMonthlyOverviewCommissionGrid()
    {
        $grid = $this->monthlyOverviewCommissionGrid->create();
        $grid->onDetail[] = function ($month, $year) {
            $this->redirect('month', ['month' => $month, 'year' => $year]);
        };
        return $grid;
    }

    public function createComponentMonthlyCommissionGrid()
    {
        $grid = $this->monthlyCommisssionGrid->create();
        $grid->setDate($this->month, $this->year);
        $grid->onGetPdf[] = function ($id, $month, $year) {
            $this->redirect('getPdf', ['userId' => $id, 'month' => $month, 'year' => $year]);
        };
        return $grid;
    }

    public function actionGetPdf($userId, $month, $year)
    {
        $response = $this->monthlyRunService->makePdf($userId, $month, $year);
        header("Content-type:application/pdf");
        $filename = "$userId-$year-$month.pdf";
        header("Content-Disposition:attachment;filename=$filename");
        echo $response;
        exit();
    }
}