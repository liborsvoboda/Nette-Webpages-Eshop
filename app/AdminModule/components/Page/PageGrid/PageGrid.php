<?php

namespace App\AdminModule\Components\Page;

use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\LocaleRepository;
use App\Model\Page\PageRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class PageGrid extends Control
{
    private $pageRepository, $gridFactory, $formFactory, $localeRepository, $langId = 1;

    public $onDone = [], $onEdit = [], $onLocaleChange = [];

    public function __construct(PageRepository $pageRepository, GridFactory $gridFactory, FormFactory $formFactory, LocaleRepository $localeRepository)
    {
        $this->pageRepository = $pageRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('page.id');
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText(':page_lang.title', 'Název')
            ->setRenderer(function ($row) {
                return $row->title;
            });
        $grid->addColumnText(':page_lang.slug', 'Slug')
            ->setRenderer(function ($row) {
                return $row->slug;
            });
        $grid->addColumnText('active', 'Viditelné')
            ->setAlign('center')
            ->setRenderer([$this, 'showActive']);
        $grid->addAction('edit', '', 'Edit', ['id'])
            ->setIcon('edit');
        return $grid;
    }

    public function createComponentLangForm()
    {
        $form = $this->formFactory->create();
        $form->addSelect('lang_id', 'form.language', $this->localeRepository->getLangsToSelect())->setDefaultValue($this->langId);
        $form->addSubmit('submit', 'form.change');
        $form->onSuccess[] = [$this, 'langFormSuccess'];
        return $form;
    }

    public function langFormSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->langId = $values->lang_id;
        $this->onLocaleChange($this->langId);
        $this['grid']->redrawControl();
    }

    public function setLocaleId($id)
    {
        $this->langId = $id;
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function getDataSource()
    {
        return $this->pageRepository->getAll($this->langId);
    }

    public function showActive($data)
    {
        $out = new Html();
        if ($data->active) {
            return $out->addHtml('<span class="text-success"><i class="fas fa-check"></i></span>');
        }
        return $out->addHtml('<span class="text-danger"><i class="fas fa-times"></i></span>');
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/pageGrid.latte');
    }
}