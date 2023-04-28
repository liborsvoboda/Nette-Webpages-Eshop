<?php


namespace App\FrontModule\Components\Commission;


use App\Model\Commission\CommissionRepository;
use App\Model\Factory\GridFactory;
use Nette\Application\UI\Control;
use Nette\Utils\DateTime;

class CommissionGrid extends Control
{

    private $gridFactory, $dateFrom, $dateTo, $commissionRepository, $userId;

    public $onOrderDetail = [];

    public function __construct(GridFactory $gridFactory, CommissionRepository $commissionRepository)
    {
        $this->gridFactory = $gridFactory;
        $this->commissionRepository = $commissionRepository;
        $this->dateFrom = new DateTime();
        $this->dateFrom->setTimestamp(0);
        $this->dateTo = new DateTime();
    }

    public function setFrom($from)
    {
        if($from) {
            $this->dateFrom = DateTime::createFromFormat('Y-m-d', $from);
            $this->dateFrom->setTime(0,0,0);
        }
    }

    public function setTo($to)
    {
        if($to) {
            $this->dateTo = DateTime::createFromFormat('Y-m-d', $to);
            $this->dateTo->setTime(23,59,59);
        }
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnDateTime('timestamp', 'Dátum')->setFormat('d.m.Y')->setSortable()->setFilterDateRange();
        $grid->addColumnText('uname', 'Jméno')
            ->setSortable()
            ->setFilterText();
        /*
        $grid->addColumnText('name', 'Partner/IBAN')
            ->setSortable()
            ->setRenderer(function ($row) {
                return $row->lastName . ', ' . $row->firstName . ' (' . $row->city . ')';
            });
        $grid->addColumnText('level', 'Pozícia')
            ->setSortable()
            ->setRenderer(function ($row) {
                return $row->user_level->name;
            });
        $grid->addColumnText('invoice', 'Faktura')
            ->setSortable()
            ->setRenderer(function ($row){
                return $row->invoice ? 'ÁNO' : 'NIE';
            });
        $grid->addColumnText('commission', 'Provízia')
            ->setSortable()
            ->setRenderer(function ($row) {
                $commission = $this->commissionRepository->getCommission($row->id, $this->dateFrom, $this->dateTo);
                return number_format($commission, 2, ',', '');
            });
        $grid->addColumnText('bonus', 'Motivačný bonus')
            ->setSortable()
            ->setRenderer(function ($row) {
                $bonus = $this->commissionRepository->getBonus($row->id, $this->dateFrom, $this->dateTo);
                return number_format($bonus, 2, ',', '');
            });
        $grid->addColumnText('all_commission', 'Celková provízia')
            ->setSortable()
            ->setRenderer(function ($row) {
                $all = $this->commissionRepository->getAllCommission($row->id, $this->dateFrom, $this->dateTo);
                return number_format($all, 2, ',', '');
            });
        */
        $grid->addColumnText('commission', 'Provízia')->setSortable()
            ->setRenderer(function ($row) {
                return $row->commission . ' ' . $row->locale->currency->symbol;
            });
        $grid->addColumnText('onumber', 'Objednávka')->setSortable()->setFilterText();
        $grid->addColumnText('order_item_id', 'Produkt')
            ->setRenderer(function ($row){
                $product = $row->order_item->product->related('product_lang')->where('lang_id', 1)->fetch();
                return $product->name.' ('.($row->order_item->count).' ks)';
            });
        //$grid->addColumnDateTime('timestamp', 'Datum')->setFormat('d.m.Y h:i:s')->setSortable();
        $grid->addAction('detail', 'Detail', 'orderDetail', ['orders_id'])->setClass('');
        $grid->setDefaultPerPage(20);
        return $grid;
    }

    public function handleOrderDetail($orders_id)
    {
        $this->onOrderDetail($orders_id);
    }

    private function getDataSource()
    {
        return $this->commissionRepository->getAllCommissionsForUser($this->dateFrom, $this->dateTo, $this->userId)
            ->select('user_commission.*, CONCAT(referee.firstName," ", referee.lastName) uname, order.number onumber')->fetchAll();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/commissionGrid.latte');
    }
}