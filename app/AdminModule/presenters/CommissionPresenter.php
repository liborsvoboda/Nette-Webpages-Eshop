<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Commission\ICommissionGridFactory;

class CommissionPresenter extends OverviewPresenter
{
    /**
     * @var ICommissionGridFactory
     * @inject
     */
    public $commissionGrid;

    public function createComponentCommissionGrid()
    {
        $grid = $this->commissionGrid->create();
        $grid->setFrom($this->from);
        $grid->setTo($this->to);
        return $grid;
    }
}