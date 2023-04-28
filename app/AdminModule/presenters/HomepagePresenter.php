<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Order\IOrderGridFactory;
use App\Model\Commission\CommissionRepository;
use App\Model\Commission\MonthlyCommissionRepository;
use App\Model\Commission\MonthlyRunService;
use App\Model\Feed\FeedRepository;
use App\Model\Fhb\FhbService;
use App\Model\Import\CMPCartImportService;
use App\Model\Import\appImportService;
use App\Model\LocaleRepository;
use App\Model\Order\OrderRepository;
use App\Model\Services\PdfService;
use App\Model\SuperFaktura\SuperFakturaService;
use App\Model\User\UserRepository;
use Nette\Utils\DateTime;

final class HomepagePresenter extends AdminPresenter
{

    /**
     * @var OrderRepository
     * @inject
     */
    public $orderRepository;

    /**
     * @var IOrderGridFactory
     * @inject
     */
    public $orderGrid;

    /**
     * @var LocaleRepository
     * @inject
     */
    public $localeRepository;


    /**
     * @var FeedRepository
     * @inject
     */
    public $feedRepository;

    /**
     * @var appImportService
     * @inject
     */
    public $importService;

    /**
     * @var FhbService
     * @inject
     */
    public $fhbService;

    /**
     * @var MonthlyCommissionRepository
     * @inject
     */
    public $monthlyCommissionRepository;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var SuperFakturaService
     * @inject
     */
    public $superFakturaService;

    /**
     * @var CommissionRepository
     * @inject
     */
    public $commissionRepository;

    /**
     * @var MonthlyRunService
     * @inject
     */
    public $monthlyRunService;

    /**
     * @var PdfService
     * @inject
     */
    public $pdfService;


    public function actionDefault()
    {
        $this->template->allOrdersSum = $this->orderRepository->getAllSumPrice();
        $this->template->todayOrdersSum = $this->orderRepository->getTodaySumPrice();
        $this->template->weekOrdersSum = $this->orderRepository->getWeekSumPrice();
        $this->template->monthOrdersSum = $this->orderRepository->getMonthSumPrice();
        $this->template->yearOrdersSum = $this->orderRepository->getYearSumPrice();
        $this->template->allOrdersSumCzk = $this->orderRepository->getAllSumPrice(true);
        $this->template->todayOrdersSumCzk = $this->orderRepository->getTodaySumPrice(true);
        $this->template->weekOrdersSumCzk = $this->orderRepository->getWeekSumPrice(true);
        $this->template->monthOrdersSumCzk = $this->orderRepository->getMonthSumPrice(true);
        $this->template->yearOrdersSumCzk = $this->orderRepository->getYearSumPrice(true);
        /*
                $this->template->allProfitability = $this->orderRepository->getAllProfitability();
                $this->template->todayProfitability = $this->orderRepository->getTodayProfitability();
                $this->template->weekProfitability = $this->orderRepository->getWeekProfitability();
                $this->template->monthProfitability = $this->orderRepository->getMonthProfitability();
                $this->template->yearProfitability = $this->orderRepository->getYearProfitability();
        */
    }

    public function createComponentOrderGrid()
    {
        $grid = $this->orderGrid->create();
        $grid->allOrders = true;
        $grid->onDetail[] = function ($id) {
            $this->redirect('Order:detail', $id);
        };
        $grid->onEdit[] = function ($id) {
            $this->redirect('Order:edit', $id);
        };
        return $grid;
    }

    public function actionMonrun()
    {
        $this->monthlyRunService->makeMonthlyRun(02, 2022);
        die;
    }

}