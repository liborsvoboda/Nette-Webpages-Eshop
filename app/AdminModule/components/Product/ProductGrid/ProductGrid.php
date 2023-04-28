<?php


namespace App\AdminModule\Components\Product;


use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\LocaleRepository;
use App\Model\Product\PriceFacade;
use App\Model\Product\ProductRepository;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Html;

class ProductGrid extends Control
{
    private $productRepository, $gridFactory, $formFactory, $localeRepository, $langId = 1, $priceFacade, $userLevelRepository;

    public $onEdit = [], $onEditGallery = [];

    public function __construct(ProductRepository $productRepository,
                                GridFactory $gridFactory,
                                FormFactory $formFactory,
                                LocaleRepository $localeRepository,
                                UserLevelRepository $userLevelRepository,
                                PriceFacade $priceFacade)
    {
        $this->productRepository = $productRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
        $this->priceFacade = $priceFacade;
        $this->userLevelRepository = $userLevelRepository;
    }

    public function createComponentGrid()
    {
        $currencyId = $this->localeRepository->getCurrencyIdByLangId($this->langId);
        $currencySymbol = $this->localeRepository->getCurrencySymbolByLangId($this->langId);
        $localeId = 1;
        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('product_id');
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('sku', 'Kód');
        $grid->addColumnText('image', 'Obrázek')
            ->setAlign('center')
            ->setTemplate(__DIR__ . '/templates/productImage.latte');
        $grid->addColumnText('name', 'Název');
        $userLevels = $this->userLevelRepository->getAllGroups();
        foreach ($userLevels as $userLevel) {
            $userLevelId = $userLevel->id;
            $grid->addColumnText('price'.$userLevelId, $userLevel->name)
                ->setRenderer(function ($row) use ($userLevelId, $localeId){
                    $priceLevels = $this->productRepository->getPriceItems($row->id, $localeId);
                    return $priceLevels[$userLevelId] ?? 0;
                });
        }
        $grid->addColumnText('commission', 'Prov')
            ->setAlign('center')
            ->setRenderer(function ($product) {
                $out = new Html();
                if ($product->commission == 1) {
                    $out->addHtml('<span class="badge badge-success">áno</span>');
                } else {
                    $out->addHtml('<span class="badge badge-primary">nie</span>');
                }
                return $out;
            });
        $grid->addColumnText('active', 'Stav')
            ->setAlign('center')
            ->setRenderer(function ($product) {
                $out = new Html();
                if ($product->active == 1) {
                    $out->addHtml('<span class="badge badge-success">Aktivní</span>');
                } else {
                    $out->addHtml('<span class="badge badge-primary">Neaktivní</span>');
                }
                return $out;
            });
        $grid->addColumnNumber('inStock', 'Na skladě')->setAlign('center')
            ->setRenderer(function ($row) {
                return $row->inStock ?? 0;
            });
        $grid->addColumnText('vat', 'DPH')
            ->setRenderer(function ($item) use ($localeId){
                return $this->priceFacade->getVat($item->id, $localeId);
            });
		$grid->addColumnText('sort', 'Poradie');
        $grid->addAction('edit', '', 'Edit', ['product_id'])
            ->setIcon('edit');
        $grid->addAction('gallery', '', 'Gallery', ['product_id'])
            ->setIcon('images');
        $grid->addAction('duplicate', '', 'Duplicate', ['product_id'])
            ->setIcon('clone');
        $grid->addFilterText('name', 'Hledat', ['name', 'sku']);
/*
        $grid->addGroupTextAction('Zmena predajnej MO ceny')
            ->onSelect[] = [$this, 'changePrice'];
        $grid->addGroupTextAction('Zmena štandardizovanej VO ceny')
            ->onSelect[] = [$this, 'changeBasePrice'];

        $grid->addGroupTextAction('Percentuálna zmena predajnej MO ceny')
            ->onSelect[] = [$this, 'changePricePercent'];
        $grid->addGroupTextAction('Percentuálna zmena štandardizovanej VO ceny')
            ->onSelect[] = [$this, 'changeBasePricePercent'];
        $grid->addGroupTextAction('Percentuálna zmena obidvoch MO i VO ceny')
            ->onSelect[] = [$this, 'changeAllPricePercent'];
*/
        $grid->addGroupAction('Deaktivovať produkty')
            ->onSelect[] = [$this, 'deactivateProducts'];
        $grid->addGroupAction('Aktivovať produkty')
            ->onSelect[] = [$this, 'activateProducts'];
        $grid->setOuterFilterRendering();
        $grid->setCollapsibleOuterFilters(false);
        return $grid;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addSelect('lang_id', 'form.language', $this->localeRepository->getLangsToSelect())->setDefaultValue($this->langId);
        $form->addSubmit('submit', 'form.change');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->langId = $values->lang_id;
        $this['grid']->redrawControl();
    }

    public function handleEdit($product_id)
    {
        $this->onEdit($product_id);
    }
    public function handleDuplicate($product_id)
    {
        $this->productRepository->duplicateProduct($product_id);
    }

    public function deactivateProducts($product_id)
    {
        $this->productRepository->changeActive($product_id, false);
        $this->redirect('this');
    }
    
    public function activateProducts($product_id)
    {
        $this->productRepository->changeActive($product_id, true);
        $this->redirect('this');
    }

    public function changePrice($ids,$value)
    {
        $this->productRepository->updatePricesArray($value,$ids);
        $this->redirect('this');
    }
    public function changeBasePrice($ids,$value)
    {
        $this->productRepository->updateBasePricesArray($value,$ids);
        $this->redirect('this');
    }

    public function changePricePercent($ids, $percent) {
        $percent = floatval(str_replace(',', '.', $percent));
        $this->productRepository->updatePricePercent($ids, $percent, ['price']);
        $this->redirect('this');
    }
    public function changeBasePricePercent($ids, $percent) {
        $percent = floatval(str_replace(',', '.', $percent));
        $this->productRepository->updatePricePercent($ids, $percent, ['base_price']);
        $this->redirect('this');
    }
    public function changeAllPricePercent($ids, $percent) {
        $percent = floatval(str_replace(',', '.', $percent));
        $this->productRepository->updatePricePercent($ids, $percent, ['price', 'base_price']);
        $this->redirect('this');
    }

    public function handleGallery($product_id)
    {
        $this->onEditGallery($product_id);
    }


    private function getDataSource()
    {
        return $this->productRepository->getAll($this->langId);
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/productGrid.latte');
    }
}