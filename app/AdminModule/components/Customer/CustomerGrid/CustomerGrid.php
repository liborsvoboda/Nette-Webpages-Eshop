<?php

namespace App\AdminModule\Components\Customer;

use App\Model\Commission\CommissionRepository;
use App\Model\Customer\CustomerRepository;
use App\Model\Factory\GridFactory;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;
use Nette\Utils\DateTime;
use Nette\Database\ForeignKeyConstraintViolationException;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class CustomerGrid extends Control
{
    public $onEdit = [], $onLoginAs = [], $onDelete = [];

    private $customerRepository, $gridFactory, $commissionRepository, $dateFrom, $dateTo, $userRepository;

    public function __construct(CustomerRepository $customerRepository, GridFactory $gridFactory, CommissionRepository $commissionRepository, UserRepository $userRepository)
    {
        $this->customerRepository = $customerRepository;
        $this->gridFactory = $gridFactory;
        $this->commissionRepository = $commissionRepository;
        $this->userRepository = $userRepository;
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

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('firstName', 'Jméno')
            ->setRenderer(function ($row) {
                return $row->firstName.' '.$row->lastName;
            });
//        $grid->addColumnText('lastName', 'Příjmení');
        $grid->addColumnText('email', 'E-mail')
                    ->addCellAttributes(['width' => '250px']);
        $grid->addColumnText('phone', 'Telefon')
                    ->addCellAttributes(['width' => '100px']);
        $grid->addColumnText('user_level.name', 'Skupina')
                    ->addCellAttributes(['width' => '100px']);
/*        $grid->addColumnText('sales', 'Obrat')
            ->setRenderer(function ($row){
                $sales = $this->commissionRepository->getSales($row->id, $this->dateFrom, $this->dateTo);
                return number_format($sales, 2, ',', '');
            });
        $grid->addColumnText('unpaid', 'Neuhradené obj.')
            ->setRenderer(function ($row){
                $unpaid = $this->commissionRepository->getUnpaidOrders($row->id, $this->dateFrom, $this->dateTo);
                return number_format($unpaid, 2, ',', '');
            });
*/
        $grid->addColumnStatus('active', 'Aktivní')
            ->addOption(0, 'Nie')
            ->setClass('btn-danger float-right')
            ->endOption()
            ->addOption(1, 'Áno')
            ->setClass('btn-success float-right')
            ->endOption()
            ->onChange[] = [$this, 'changeActive'];
        $grid->addAction('edit', '', 'Edit', ['id'])
            ->setTitle('Upravit')
            ->setIcon('edit');
        $grid->addAction('delete', '', 'Delete', ['id'])
            ->setConfirmation(new StringConfirmation('POZOR!!! Chcete opravdu uživatele smazat?'))
            ->setTitle('Odstranit')
            ->setIcon('trash');
        if($this->userRepository->isLoggedUserAdmin()) {
            $grid->addAction('loginas', '', 'loginAs')
                ->setTitle('Přihlásit jako')
                ->setIcon('user-tag');
        }
        $grid->addFilterText('name', 'Hledat', ['firstName', 'lastName', 'email']);
        $grid->setOuterFilterRendering(true);
        $grid->setCollapsibleOuterFilters(false);
        $grid->setTreeView([$this,'getChildren'], [$this, 'hasChildren']);
        return $grid;
    }

    public function changeActive($id, $value)
    {
        $this->customerRepository->setActive($id, $value);
        $this->redrawControl();
    }

    public function showDetail($item)
    {
        $this->template->item = $item;
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function handleDelete($id)
    {
       try{
           $result = $this->customerRepository->getAll()->where('id', $id)->delete();
           /*if (!$result){
                $this->flashMessage('Uživatel nelze smazat', 'danger');
           }*/
       } catch (\Exception $e) {
           $this->flashMessage('Uživatel nelze smazat', 'danger'); //$e->getMessage()
           //$this->redrawControl("flash");
		   //throw new \Exception("Uživatel nelze smazat z důvodu existujících vazeb");
	   }
    }

    public function handleLoginAs($id)
    {
        $this->onLoginAs($id);
    }

    private function getDataSource()
    {
        //return $this->customerRepository->getAll();
        return $this->customerRepository->getChildren()->where('id > 4')->where('user_level_id > 1');
    }

    public function getChildren($parentId)
    {
        return $this->customerRepository->getChildren($parentId);
    }

    public function hasChildren($parentId)
    {
        return $this->getChildren($parentId)->count() > 0;
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/customerGrid.latte');
    }
}