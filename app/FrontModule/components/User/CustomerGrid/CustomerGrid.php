<?php

namespace App\FrontModule\Components\Customer;

use App\Model\Commission\CommissionRepository;
use App\Model\Customer\CustomerRepository;
use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class CustomerGrid extends Control
{
    public $onEdit = [], $onDetail = [], $onSearch = [];

    private $customerRepository, $gridFactory, $commissionRepository, $dateFrom, $dateTo, $parentId = null, $formFactory, $searchString = '', $userLevelRepository;

    public function __construct(CustomerRepository $customerRepository,
                                GridFactory $gridFactory,
                                CommissionRepository $commissionRepository,
                                FormFactory $formFactory,
                                UserLevelRepository $userLevelRepository)
    {
        $this->customerRepository = $customerRepository;
        $this->gridFactory = $gridFactory;
        $this->commissionRepository = $commissionRepository;
        $this->formFactory = $formFactory;
        $this->userLevelRepository = $userLevelRepository;
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

    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('firstName', 'Meno a priezvisko')
        ->setRenderer(function ($row) {
            return $row->firstName.' '.$row->lastName;
        });
//        $grid->addColumnDateTime('registered_at', 'Dátum registrácie')->setFormat('Y-m-d');
        $grid->addColumnText('email', 'E-mail');
        $grid->addColumnText('phone', 'Telefon');
        $grid->addColumnText('user_level.name', 'AKS');
        /*
        $grid->addColumnText('sales', 'Obrat')
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
        $grid->addAction('edit', 'Detail', 'Edit', ['id'])
            ->setClass('');
        $grid->setTreeView([$this,'getChildren'], [$this, 'hasChildren']);
        return $grid;
    }

    public function createComponentSearchForm()
    {
        $form = $this->formFactory->create();
        $form->addText('search', '')->setRequired()->setHtmlAttribute('class', 'search-string');
        $form->addSubmit('submit', 'Hľadať');
        $form->setHtmlAttribute('class', 'ajax');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::INLINE));
        $form->onSuccess[] = [$this, 'searchFormSuccess'];
        return $form;
    }

    public function searchFormSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->searchString = $values->search;
//        $this->onSearch($values->search);
        $this->redrawControl('searchGrid');
    }

    public function createComponentSearchGrid()
    {
        $levels = $this->userLevelRepository->getForSelect();
        $month = date('m');
        $year = date('Y');
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getSearchDataSource());
        $grid->addColumnText('ref_no', 'Ref. číslo');
        $grid->addColumnText('firstName', 'Meno, mesto')
            ->setRenderer(function ($row) {
                return $row->firstName.' '.$row->lastName.', '.$row->city;
            });
        $grid->addColumnText('user_level_id', 'Kariérny stupeň')
            ->setRenderer(function ($row) use ($levels) {
                return $levels[$row->user_level_id];
            });
        $grid->addColumnText('email', 'E-mail');
        $grid->addColumnText('phone', 'Telefon');
        //$grid->addColumnText('max_level', 'NHKS')
        //    ->setRenderer(function ($row) use ($levels) {
        //        return ($row->max_level) ? $levels[$row->max_level] : $levels[$row->user_level_id];
        //    });
        $grid->addAction('detail', 'Detail', 'detail')
            ->setClass('');
        $grid->setRememberState(false);
        return $grid;
    }

    private function getSearchDataSource()
    {
        if(strlen($this->searchString) < 3) return [];
        $res = $this->customerRepository->getAll()
        //->select('DISTINCT user.*')
        //->select('IFNULL((SELECT MAX(:user_group_change.user_group_id) FROM user_group_change WHERE user.id = :user_group_change.user_id),NULL) AS max_level')
        //->joinWhere(':user_group_change',':user_group_change.user_id = user.id')
        ->whereOr([
            'firstName LIKE ?' => '%'.$this->searchString.'%',
            'lastName LIKE ?' => '%'.$this->searchString.'%',
            'phone LIKE ?' => '%'.$this->searchString.'%',
            'email LIKE ?' => '%'.$this->searchString.'%',
            'ref_no LIKE ?' => '%'.$this->searchString.'%'
        ])
        //->order('user.id');
        ;
        return $res;
    }

    public function handleDetail($id)
    {
        $this->onDetail($id);
    }

    public function showDetail($item)
    {
        $this->template->item = $item;
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    private function getDataSource()
    {
        return $this->customerRepository->getChildren($this->parentId, true);
    }

    public function getChildren($parentId)
    {
        return $this->customerRepository->getChildren($parentId, true);
    }

    public function hasChildren($parentId)
    {
        return $this->getChildren($parentId)->count() > 0;
    }

    public function render()
    {
        $this->template->showSearchgrid = false;
        if(count($this->getSearchDataSource()) > 0) {
            $this->template->showSearchgrid = true;
        };
        $this->template->render(__DIR__.'/templates/customerGrid.latte');
    }
}