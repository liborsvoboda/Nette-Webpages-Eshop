<?php

namespace App\FrontModule\Presenters;

use App\Model\Product\ProductRepository;
use App\Model\Search\SearchRepository;
use App\FrontModule\Components\Category\ICategorySearchFactory;
use App\FrontModule\Components\Category\CategorySearch;
use App\FrontModule\Components\Product\IProductSortingFormFactory;
use App\FrontModule\Components\Product\ProductSortingForm;
use Nette\Application\UI\Form;

class SearchPresenter extends BasePresenter
{
	/**
	 * @var ICategorySearchFactory
	 * @inject
	 */
	public $categorySearchFactory;

	/**
     * @var IProductSortingFormFactory
     * @inject
     */
    public $productSortingFactory;

    /**
     * @var ProductRepository
     * @inject
     */
    public $productRepository;

    /** @var string */
    private $search;

    /** @var int */
    public $page = 1;

    /** @var int */
    public $lastPage = 0;

    /** @var int */
    private $sorting = 1;

	public function actionDefault(string $hladat = NULL)
	{
		if ($hladat === NULL) {
			$this->redirect('Homepage:default');
		}
		$this->search = $hladat;
		$this->template->search = $hladat;
		$this->sorting = $this['productSort']->getSorting() ?? 1;
	}

	public function renderDefault()
    {
        $this['nextPageForm']->setValues(['next' => $this->page + 1]);
        $this->template->products = $this->getProducts();
        $this->template->page = $this->page;
        $this->template->lastPage = $this->lastPage;
    }

	/**
	 * @return CategorySearch
	 */
	protected function createComponentCategorySearch(): CategorySearch
	{
		return $this->categorySearchFactory->create($this->search);
	}

	/**
	 * @return ProductSortingForm
	 */
	protected function createComponentProductSort(): ProductSortingForm
	{
		$productSort = $this->productSortingFactory->create('searchProductSort');
        $productSort->setIsAjax(false);
        $productSort->onDone[] = function ($sorting) {
            $this->sorting = $sorting;
            if ($this->isAjax()) {
                $this->redrawControl('productList');
        		$this->redrawControl('pagination');
            } else {
                $this->redirect('this');
            }
        };
        return $productSort;
	}

	protected function createComponentNextPageForm()
    {
        $form = new Form();
        $form->addHidden('next', $this->page + 1);
        $form->addSubmit('nextPage');
        $form->onSuccess[] = [$this, 'getNextPage'];
        return $form;
    }

    public function getNextPage(Form $form)
    {
        $values = $form->getValues();
        $this->page = (int) $values->next;
        $this->redrawControl('productList');
        $this->redrawControl('pagination');
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

	private function getProducts()
    {
        $this->productRepository->setSorting($this->sorting);
        return $this->productRepository->getSearched($this->search)->page($this->page, 12, $this->lastPage);
    }
}