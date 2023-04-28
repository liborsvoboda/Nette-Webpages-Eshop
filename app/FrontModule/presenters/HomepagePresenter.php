<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Category\ICategoryFilterFactory;
use App\FrontModule\Components\ContactForm\IContactFormFactory;
use App\FrontModule\Components\Product\IProductLineFactory;
use App\FrontModule\Components\Product\IProductSortingFormFactory;
use App\Model\Blog\BlogRepository;
use App\Model\Commission\MonthlyRunService;
use App\Model\Import\ImportService;
use App\Model\Order\OrderRepository;
use App\Model\Product\ProductRepository;
use App\Model\Services\UserManager;
use App\Model\Slider\SliderRepository;
use App\Model\User\UserRepository;
use Nette\Http\Response;
use Nette\Utils\DateTime;

final class HomepagePresenter extends BasePresenter
{
    /**
     * @var IProductLineFactory
     * @inject
     */
    public $productLine;

    /**
     * @var ProductRepository
     * @inject
     */
    public $productRepository;

    /**
     * @var BlogRepository
     * @inject
     */
    public $blogRepository;

    /**
     * @var ICategoryFilterFactory
     * @inject
     */
    public $categoryFilterFactory;

    /**
     * @var IProductSortingFormFactory
     * @inject
     */
    public $productSortingFactory;

    /**
     * @var IContactFormFactory
     * @inject
     */
    public $contactFormFactory;

    /**
     * @var SliderRepository
     * @inject
     */
    public $sliderRepository;

    /**
     * @var ImportService
     * @inject
     */
    public $importService;

    /**
     * @var OrderRepository
     * @inject
     */
    public $orderRepository;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var MonthlyRunService
     * @inject
     */
    public $monthlyRunService;

    private $sfKey = 'd49201e06041553c3f9b9717b3e849be';

    private $categoryFilter = null, $sorting = 1;

    public function renderDefault()
    {
        $this->categoryFilter = $this['categoryFilter']->getFiltered();
        $this->sorting = $this['productSort']->getSorting() ?? 1;
        $this->template->slides = $this->sliderRepository->getAllByLang($this->langId);
        $banners = $this->sliderRepository->getAllBannersWithProductCount();
        $this->template->products = $this->getFeatured() ?? [];
        $this->template->product = $this->getFeatured()->fetch();
        $this->template->originalPrice = $this->getFeatured()->fetch()->original_price;
        $this->template->blogs = $this->blogRepository->getAll(null, true)->order('timestamp DESC')->limit(8);
        $this->template->banners = $banners;
    }

    public function createComponentCategoryFilter()
    {
        $categoryFilter = $this->categoryFilterFactory->create('homePageCategoryFilter', null);
        $categoryFilter->onDone[] = function ($filtered) {
            $this->categoryFilter = $filtered;
            //$this->redrawControl('productHome');
            if ($this->isAjax()) {
                $this['categoryFilter']->redrawControl();
                $this['productLine']->redrawControl();
            } else {
                $this->redirect('this');
            }
        };
        return $categoryFilter;
    }

    public function createComponentProductSort()
    {
        $productSort = $this->productSortingFactory->create('homePageSorting');
        $productSort->onDone[] = function ($sorting) {
            $this->sorting = $sorting;
            if ($this->isAjax()) {
                $this['productSort']->redrawControl();
                $this['productLine']->redrawControl();
            } else {
                $this->redirect('this');
            }
        };
        return $productSort;
    }

    public function createComponentProductLine()
    {
        $productLine = $this->productLine->create($this->productRepository->getFeatured(8));
        // $productLine = $this->productLine->create($this->getProducts());
        $productLine->setAllowCart($this->allowCart);
        return $productLine;
    }

    private function getProducts()
    {
        $subIds = null;
        if ($this->categoryFilter) {
            $subIds = $this->categoryFilter . ',';
            foreach (explode(',', $this->categoryFilter) as $item) {
                $subIds .= $this->categoryRepository->getSubIds($item);
            }
        }
        if ($subIds && strlen($subIds) > 0) {
            $subIds = rtrim($subIds, ",");
            $subs = explode(',', $subIds);
        } else {
            $subs = null;
        }
        $this->productRepository->setSorting($this->sorting);
        return $this->productRepository->getForHomepage(8);
    }

    public function getFeatured()
    {
        return $this->productRepository->getFeatured();
    }

    public function createComponentContactForm()
    {
        return $this->contactFormFactory->create();
    }

    public function actionError()
    {
        $this->getHttpResponse()->setCode(404);
    }

    public function action404()
    {
        $this->getHttpResponse()->setCode(404);
    }

    public function actionSfCallback()
    {
        if ($this->getHttpRequest()->getQuery('secret_key') == $this->sfKey) {
            $this->orderRepository->setPaidBySfId($this->request->getParameter('invoice_id'));
        } else {
            echo 'Bad secret';
        }
        $this->terminate();
    }

    public function actionLoginas($id)
    {
        if ($this->userRepository->isLoggedUserAdmin()) {
            $this->userRepository->loginAsUser($id);
            $this->userRepository->unsetInfoSession();
            $this->userRepository->copyInfoToSession($id);
            $cart = $this->session->getSection('cart');
            unset($cart->items);
            $this->redirect('default');
        }
    }

    public function actionUpdateExchangeRate()
    {
        $apikey = '1ead668fad0d4fe7b65bbb42ba0f166d';//'81626c4bbf7542f4955d52e40be9ce7d';
        $czkeur = file_get_contents("https://free.currconv.com/api/v7/convert?q=EUR_CZK&compact=ultra&apiKey={$apikey}");
        $czkeur = json_decode($czkeur, true);
        $czkeur = floatval($czkeur['EUR_CZK']);
        $this->localeRepository->updateExchangeRate($czkeur);
        $this->terminate();
    }

    public function actionMonrun()
    {
        $date = new DateTime('first day of last month');
        $this->monthlyRunService->makeMonthlyRun($date->format('m'), $date->format('Y'));
        $this->terminate();
    }
}
