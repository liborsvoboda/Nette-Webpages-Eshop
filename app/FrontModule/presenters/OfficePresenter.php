<?php

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Commission\ICommissionGridFactory;
use App\FrontModule\Components\Office\IEditFormFactory;
use App\FrontModule\Components\Office\IPartnerGridFactory;
use App\FrontModule\Components\Order\IOrderDetailFactory;
use App\FrontModule\Components\Order\IOrderGridFactory;
use App\Model\Commission\MonthlyCommissionRepository;
use App\Model\Commission\MonthlyRunService;
use App\Model\Customer\CustomerRepository;
use App\Model\Office\OfficeRepository;
use App\Model\Order\OrderRepository;
use App\Model\User\UserRepository;
use App\Model\LocaleRepository;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\LinkGenerator;

class OfficePresenter extends BasePresenter
{
    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var LinkGenerator
     * @inject
     */
    public $linkGenerator;

    /**
     * @var OrderRepository
     * @inject
     */
    public $orderRepository;

    /**
     * @var IPartnerGridFactory
     * @inject
     */
    public $partnerGrid;

    /**
     * @var IOrderGridFactory
     * @inject
     */
    public $orderGrid;

    /**
     * @var IOrderDetailFactory
     * @inject
     */
    public $orderDetail;

    /**
     * @var OfficeRepository
     * @inject
     */
    public $officeRepository;

    /**
     * @var LocaleRepository
     * @inject
     */
    public $localeRepository;

    /**
     * @var ICommissionGridFactory
     * @inject
     */
    public $commissionsGrid;

    /**
     * @var UserLevelRepository
     * @inject
     */
    public $userLevelRepository;

    /**
     * @var CustomerRepository
     * @inject
     */
    public $customerRepository;

    /**
     * @var IEditFormFactory
     * @inject
     */
    public $userEditForm;

    /**
     * @var MonthlyRunService
     * @inject
     */
    public $monthlyRunService;

    /**
     * @var MonthlyCommissionRepository
     * @inject
     */
    public $monthlyCommissionRepository;

    /** @persistent **/
    public $uid = null;

    private $paidOrders = true, $orderId;


    public function startup()
    {
        parent::startup();
        if(!$this->user->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
        $uid = $this->getParameter('uid');
        $this->template->uid = $uid;
        $this->uid = $uid ?? $this->user->id;
        $allowedUsers = [$this->getUser()->getId()] + $this->userRepository->getAllParents($uid);
        if(!in_array($this->getUser()->getId(), $allowedUsers) && $this->uid != $this->getUser()->getId()) {
            $this->redirect('Homepage:default');
        }
        $userData = $this->userRepository->getById($this->uid)->fetch();
        if(!$userData) {
            $userData = $this->userRepository->getById($this->user->id)->fetch();
        }
        $this->template->userData = $userData;
        $this->template->rootUrl = $this->linkGenerator->link('Front:Homepage:');
        $referral = null;
        if($userData->referral_id) {
            $referralData = $this->userRepository->getById($userData->referral_id)->fetch();
            $referral = $referralData->firstName . ' ' . $referralData->lastName . ' (' . $referralData->ref_no . ')';
            $this->template->referralId = $referralData->id;
        }
        $this->template->referral = $referral;
        $this->template->levels = $this->userLevelRepository->getForSelect();
        $this->template->directPartnersCount = $this->userRepository->getDirectReferees($this->uid)->count('id');
        $this->template->allPartnersCount = count(explode(',' ,$this->customerRepository->getSubIds($this->uid)));
    }

    public function actionDefault()
    {
        $this->template->turnovers = $this->officeRepository->getTurnovers($this->uid);
    }

    public function actionStats()
    {
        $selfTurnovers = [];
        $currencyId = $this->localeRepository->getCurrencyId();
        $this->template->turnovers = $this->officeRepository->getTurnovers($this->uid);
        $monthlyTurnovers = $this->monthlyRunService->getMonthlyTurnover($this->uid);
        foreach ($monthlyTurnovers as $turnover) {
            $selfTurnovers[$turnover->year][$turnover->month] = $this->monthlyCommissionRepository->sumSelfMonthTurnover($this->uid, $turnover->month, $turnover->year, $currencyId) ?? 0;
        }
        $userData = $this->userRepository->getById($this->uid)->fetch();
        $this->template->monthlyTurnovers = $monthlyTurnovers;
        $this->template->selfTurnovers = $selfTurnovers;
        $this->template->currency = $userData->country_id == 2 ? 'Kč' : '€';
        $this->template->inCzk = $userData->country_id == 2;
        $this->template->czkExchangeRate = $this->localeRepository->getExchangeRate(2);
    }

    public function actionUnpaidOrders()
    {
        $this->paidOrders = false;
    }

    public function actionPaidOrders()
    {
        $this->paidOrders = true;
    }

    public function actionOrderDetail($orderId)
    {
        $this->orderId = $orderId;
    }

    public function actionPartners()
    {

    }

    public function createComponentPartnerGrid()
    {
        $grid = $this->partnerGrid->create();
        $grid->setUserId($this->uid);
        $grid->onDetail[] = function ($uid) {
            $this->redirect('partners', ['uid' => $uid]);
        };
        return $grid;
    }

    public function createComponentOrderGrid()
    {
        $grid = $this->orderGrid->create();
        $grid->setUserId($this->uid);
        $grid->setPaid($this->paidOrders);
        $grid->onDetail[] = function ($id) {
            $this->redirect('orderDetail', $id);
        };
        return $grid;
    }

    public function createComponentOrderDetail()
    {
        $detail = $this->orderDetail->create($this->orderId);
        return $detail;
    }

    public function createComponentCommissionsGrid()
    {
        $grid = $this->commissionsGrid->create();
        $grid->setUserId($this->uid);
        $grid->onOrderDetail[] = function ($id) {
            $this->redirect('orderDetail', $id);
        };
        return $grid;
    }

    public function createComponentEditForm()
    {
        $form = $this->userEditForm->create();
        $form->setUserId($this->uid);
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }

}