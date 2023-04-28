<?php


namespace App\FrontModule\Presenters;


use App\FrontModule\Components\Category\ICategoryFilterFactory;
use App\FrontModule\Components\Product\IProductFilterFactory;
use App\FrontModule\Components\Product\IProductSortingFormFactory;
use App\FrontModule\Components\Category\CategoryTree;
use App\FrontModule\Components\Category\ICategoryTreeFactory;
use App\Model\Category\CategoryRepository;
use App\Model\Producer\ProducerRepository;
use App\Model\Product\ProductRepository;
use App\Model\LocaleRepository;
use Nette\Application\BadRequestException;
use Nette\Utils\ArrayHash;

class CategoryPresenter extends BasePresenter
{
    /**
     * @var CategoryRepository
     * @inject
     */
    public $categoryRepository;

    /**
     * @var ProductRepository
     * @inject
     */
    public $productRepository;

    /**
     * @var ProducerRepository
     * @inject
     */
    public $producerRepository;

    /**
     * @var LocaleRepository
     * @inject
     */
    public $localeRepository;

    /**
     * @var IProductFilterFactory
     * @inject
     */
    public $productFilter;

    /**
     * @var ICategoryTreeFactory
     * @inject
     */
    public $categoryTree;

    /**
     * @var IProductSortingFormFactory
     * @inject
     */
    public $productSortingFactory;

    private $sorting = 1, $categoryId, $priceFrom, $priceTo, $producers, $attributes, $onlyStock;

    public $page = 1, $lastPage;

    public function actionDefault($slug)
    {
        $categoryId = $slug;
        $this->categoryId = $categoryId;
        $subIds = $this->categoryRepository->getSubIds($categoryId);
        $category = $this->categoryRepository->getById($categoryId);
        $this['productFilter']->setCategories($this->categoryId . ','.$this->categoryRepository->getSubIds($this->categoryId));
        $parents = $this->categoryRepository->getParents($categoryId);
        $this->template->category = $category;
        $this->template->parents = $parents;
        $this->template->children = $this->categoryRepository->getChildren($categoryId, true);

        $breads = [];
        foreach ($parents as $parent) {
            $breads[$parent->slug] = $parent->name;
        }
        $breads[$category->slug] = $category->name;
        $this->template->breads = $breads;

        $this->template->currency = $this->localeRepository->getCurrencyByLang($this->getParameter('locale'));
    }

    public function renderDefault()
    {
        $this->sorting = $this['productSort']->getSorting() ?? 1;
        $this['productFilter']->setCategories($this->categoryId . ','.$this->categoryRepository->getSubIds($this->categoryId));
        $this->priceFrom = $this['productFilter']->getPriceFrom();
        $this->priceTo = $this['productFilter']->getPriceTo();
        $this->producers = $this['productFilter']->getProducers();
        $this->attributes = $this['productFilter']->getAttributes();
        $this->onlyStock = $this['productFilter']->getOnlyStock();
        $products = $this->getProducts();
        $this->template->originalPrice = $products->fetch()->original_price;
        $this->template->productCount = $products->count('*');
        $this->template->products = $products->page($this->page, 80, $this->lastPage);
        // @todo Select bestsellers from DB
        $this->template->bestSellers = $this->productRepository->getTopSellers($this->categoryId . ','.$this->categoryRepository->getSubIds($this->categoryId));
        $this->template->page = $this->page;
        $this->template->lastPage = $this->lastPage;
    }

    public function renderProducer($slug)
    {
        if(!$slug) {
            throw new BadRequestException();
        }
        $producerId = $slug;
        $this['productFilter']->setProducers([$producerId]);
        $producer = $this->producerRepository->getById($producerId)->fetch();
        $this->setView('default');
        $this->categoryId = null;
        $this['productFilter']->setCategories($this->categoryId . ','.$this->categoryRepository->getSubIds($this->categoryId));
        $this->priceFrom = $this['productFilter']->getPriceFrom();
        $this->priceTo = $this['productFilter']->getPriceTo();
        $this->producers = $this['productFilter']->getProducers();
        $products = $this->getProducts();
        $breads  = [
            $this->link('Producer:default') => $this->translator->translate('strings.producers'),
            $this->link('Category:producer', $producer->id) => $producer->name
        ];
        $this->template->children = [];
        $this->template->breads = $breads;
        $this->template->products = $products->page($this->page, 24, $this->lastPage);
        $this->template->productCount = $products->count('*');
        $this->template->category = $producer;
        $this->template->parents = [];
        $this->template->bestSellers = $this->productRepository->getTopSellers($this->categoryId . ','.$this->categoryRepository->getSubIds($this->categoryId));
        $this->template->page = $this->page;
        $this->template->lastPage = $this->lastPage;
        $this->template->currency = $this->localeRepository->getCurrencyByLang($this->getParameter('locale'));
    }

    public function handleNextPage($page)
    {
        $this->page = $page;
        if ($this->isAjax()) {
            $this->redrawControl('products');
            $this->redrawControl('productList');
            $this->redrawControl('paginator');
        }
    }

    public function createComponentProductFilter()
    {
        $categoryFilter = $this->productFilter->create('category' . $this->categoryId . 'CategoryFilter');
        $categoryFilter->onDone[] = function() {
            if ($this->isAjax()) {
				// $this->redrawControl('categoryContent');
				$this->redrawControl('productsList');

                $this->handleNextPage(1);
            } else {
                $this->redirect('this');
            }
        };
        $categoryFilter->onReset[] = function() {
            if ($this->isAjax()) {
                $this->handleNextPage(1);
                $this->redrawControl('productFilter');
            } else {
                $this->redirect('this');
            }
        };
        return $categoryFilter;
    }

    public function createComponentCategoryTree(): CategoryTree
    {
        $tree = $this->categoryTree->create($this->categoryId);
        return $tree;
    }

    public function createComponentProductSort()
    {
        $productSort = $this->productSortingFactory->create('category' . $this->categoryId . 'ProductSort');
        $productSort->onDone[] = function ($sorting) {
            $this->sorting = $sorting;
            $this['productSort']->redrawControl();
            $this->handleNextPage(1);
        };
        return $productSort;
    }


    private function getProducts()
    {
        $this->productRepository->setPriceFrom($this->priceFrom);
        $this->productRepository->setPriceTo($this->priceTo);
        $this->productRepository->setSorting($this->sorting);
        $this->productRepository->setProducers($this->producers);
        $this->productRepository->setAttributes($this->attributes);
        $this->productRepository->setOnlyStock($this->onlyStock);
        $subIds = $this->categoryId . ','.$this->categoryRepository->getSubIds($this->categoryId);
        return $this->productRepository->getInCategories($subIds);
//         $subIds = $this->categoryId . ',';
//         if ($this->categoryFilter) {
//             $subIds .= $this->categoryFilter . ',';
//         }
//         foreach (explode(',', $this->categoryFilter) as $item) {
//             $subIds .= $this->categoryRepository->getSubIds($item);
//         }
//         if ($subIds && strlen($subIds) > 0) {
//             $subIds = rtrim($subIds, ",");
//             $subs = explode(',', $subIds);
//         } else {
//             $subs = null;
//         }
// //        $this->productRepository->setCategoryFilter($subs);
// //        $this->productRepository->setSorting($this->sorting);
//         return $this->productRepository->getFiltered();
    }


    public function actionOpportunity($tag)
    {
        $this->template->products = $this->productRepository->getByTag($tag);
        $this->template->category = $tag;
    }
}