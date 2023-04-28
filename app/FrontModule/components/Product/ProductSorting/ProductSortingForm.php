<?php


namespace App\FrontModule\Components\Product;


use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Http\Session;

class ProductSortingForm extends Control
{
    protected $appSettingsService, $session, $section, $defaultValue, $isAjax = true;

    public $onDone = [];

    public function __construct($section, AppSettingsService $appSettingsService, Session $session)
    {
        $this->appSettingsService = $appSettingsService;
        $this->session = $session;
        $this->section = $session->getSection($section);
        $this->section->setExpiration('2 minutes');
    }

    public function getSorting()
    {
        return isset($this->section->sort) ? $this->section->sort : null;
    }

    public function setIsAjax($isAjax)
    {
        $this->isAjax = $isAjax;
    }

    private function setSortSession($value)
    {
        $this->section->sort = $value;
    }

    private function getSortingArray()
    {
        $array = ProductRepository::SORTING;
        if($this->getSorting()) {
            unset($array[$this->getSorting()]);
        } else {
            unset($array[1]);
        }
        return $array;
    }

    private function getDefaultValue()
    {
        if($this->getSorting()) {
            return ProductRepository::SORTING[$this->getSorting()];
        }
        return ProductRepository::SORTING[1];
    }

    public function createComponentForm()
    {
        $form = new Form();
        $form->addRadioList('sort', '', $this->getSortingArray());
        $form->addSubmit('sortSubmit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->setSortSession($values['sort']);
        $this->onDone($values['sort']);
    }

    public function render()
    {
        $this->template->defaultValue = $this->getDefaultValue();
        $this->template->isAjax = $this->isAjax;
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/Product/ProductSorting/productSorting.latte');
    }

}