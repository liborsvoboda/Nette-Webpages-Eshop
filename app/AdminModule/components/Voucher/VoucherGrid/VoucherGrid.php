<?php


namespace App\AdminModule\Components\Voucher;


use App\Model\Voucher\VoucherRepository;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

class VoucherGrid extends Control
{
    public $onEdit = [];

    private $voucherRepository;

    public function __construct(VoucherRepository $voucherRepository)
    {
        $this->voucherRepository = $voucherRepository;
    }

    public function createComponentGrid()
    {
        $grid = new DataGrid();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('code', 'Kód');
        $grid->addColumnText('dateFrom', 'Platnost od');
        $grid->addColumnText('dateTo', 'Platnost do');
        $grid->addColumnText('priceFrom', 'Cena od');
        $grid->addColumnText('priceTo', 'Cena do');
        $grid->addColumnText('value', 'Sleva');
        $grid->addColumnText('type', 'Typ slevy')
            ->setRenderer([$this, 'showType']);
        $grid->addColumnText('parent_ref_no', 'Referenční číslo');
        $grid->addAction('edit', '', 'Edit', ['id'])
            ->setIcon('edit');
        $grid->addAction('remove', '', 'Remove', ['id'])
            ->setIcon('trash')->setTitle('Smazat')->setClass('btn btn-xs btn-danger');
        return $grid;
    }

    public function showType($data)
    {
        return VoucherRepository::VOCHER_TYPE[$data->type];
    }

    private function getDataSource()
    {
        return $this->voucherRepository->getAllLang();
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function handleRemove($id)
    {
        $this->voucherRepository->remove($id);
        $this['grid']->redrawControl();
        $this->redirect('this');
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/voucherGrid.latte');
    }
}
