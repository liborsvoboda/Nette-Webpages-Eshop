<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Profit\IProfitGridFactory;
use App\Model\Feed\FeedRepository;
use App\Model\Product\ProductRepository;

class ProfitPresenter extends CataloguePresenter
{

    /**
     * @var IProfitGridFactory
     * @inject
     */
    public $profitGrid;

    /**
     * @var ProductRepository
     * @inject
     */
    public $productRepository;

    /**
     * @var FeedRepository
     * @inject
     */
    public $feedRepository;

    public function createComponentProfitGrid()
    {
        $grid = $this->profitGrid->create();
        return $grid;
    }

    public function handleRecalculate()
    {
        $this->productRepository->recalculatePrice();
        $this->feedRepository->makeHeurekaSk();
        $this->feedRepository->makePricemaniaSk();
        $this->flashMessage('Ceny byly přepočítány');
        $this->redirect('this');
    }
}