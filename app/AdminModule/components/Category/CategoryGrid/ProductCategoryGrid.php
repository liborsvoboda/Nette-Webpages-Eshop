<?php


namespace App\AdminModule\Components\Category;

use App\Model\Category\CategoryRepository;
use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\LocaleRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class ProductCategoryGrid extends Control
{
    private $categoryRepository, $gridFactory, $localeRepository, $formFactory, $langId = 1;

    public $onEdit = [], $onLangChange = [];


    public function __construct(CategoryRepository $categoryRepository,
                                GridFactory $gridFactory,
                                LocaleRepository $localeRepository,
                                FormFactory $formFactory)
    {
        $this->categoryRepository  = $categoryRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        //$grid->setPrimaryKey('category.id');
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText(':category_lang.name', 'Název')
            ->setRenderer(function ($row){
                return $row->name;
            });
        $grid->addColumnText(':category_lang.slug', 'Slug')
            ->setRenderer(function ($row){
                return $row->slug;
            })
        ->setAlign('left');
//        $grid->addColumnText('id', 'ID');
        $grid->addAction('edit','', 'Edit', ['id'])
            ->setIcon('edit');
        $grid->setTreeView([$this,'getChildren'], [$this, 'hasChildren']);
//        $grid->setSortableHandler('productCategoryGrid:sort!');
//        $grid->setSortable();
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

    public function setLangId($langId)
    {
        $this->langId = $langId;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->langId = $values->lang_id;
        $this->onLangChange($values->lang_id);
        $this['grid']->redrawControl();
    }

    public function handlesort()
    {

    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function getChildren($parentId)
    {
        $out = $this->categoryRepository->getChildren($parentId)->order('category.id');
        return $out;
    }

    public function hasChildren($parentId)
    {
        return $this->getChildren($parentId)->count() > 0;
    }

    private function getDataSource()
    {
        $out = $this->categoryRepository->getChildren()->order('category.id');
        return $out;
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/categoryGrid.latte');
    }
}