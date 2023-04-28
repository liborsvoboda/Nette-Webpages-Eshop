<?php


namespace App\FrontModule\Components\Product;


use App\Model\Attribute\AttributeRepository;
use App\Model\Producer\ProducerRepository;
use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Http\Session;

class ProductFilter extends Control
{
    private $producerRepository, $session, $section, $appSettingsService, $productRepository, $attributeRepository;

    public $priceFrom, $priceTo, $categories, $onDone = [], $onReset = [], $producers;

    public function __construct(
        $section,
        ProducerRepository $producerRepository,
        Session $session,
        AppSettingsService $appSettingsService,
        ProductRepository $productRepository,
        AttributeRepository $attributeRepository
    )
    {
        $this->producerRepository = $producerRepository;
        $this->appSettingsService = $appSettingsService;
        $this->productRepository = $productRepository;
        $this->attributeRepository = $attributeRepository;
        $this->session = $session;
        $this->section = $session->getSection($section);
        $this->section->setExpiration('2 minutes');
    }

    public function createComponentForm()
    {
        $form = new Form();
        $form->addText('priceFrom')->setDefaultValue($this->getPriceFrom());
        $form->addText('priceTo')->setDefaultValue($this->getPriceTo());
        $form->addCheckbox('onlyStock')->setDefaultValue($this->getOnlyStock());
        // $form->addCheckboxList('producers', '', $this->getProducersSelect());
        $attributesSelect = $this->getAttributesSelect();
        if (count($attributesSelect) > 0) {
            foreach ($attributesSelect as $asKey => $asValue) {
                $form->addCheckboxList('param' . $asKey, '', $asValue);
                if (isset($this->getAttributes()[$asKey])) {
                    $form['param' . $asKey]->setDefaultValue($this->getAttributes()[$asKey]);
                }
            }
        }
        // if ($this->getProducers()) {
        //     $items = $form['producers']->items;
        //     $ids = array_intersect($items, explode(',', $this->getProducers()));
        //     $form['producers']->setDefaultValue($ids);
        // }
        $form->addCheckboxList('attributes', '', $this->getAttributesSelect());
        $form->addSubmit('filter');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }


    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    public function getProducersSelect()
    {
        $producersProduct = $this->productRepository->getProducersInCategories($this->categories);
        $producers = $this->producerRepository->getForSelect($producersProduct);
        return $producers;
    }

    public function getAttributesSelect()
    {
        return $this->productRepository->getAllAttributesToFilter($this->categories);
    }

    public function getAttributeName($attributeId)
    {
        return $this->productRepository->getAttributeNameById($attributeId);
    }

    public function getAttributeNames($attributes)
    {
        $names = [];
        foreach ($attributes as $aKey => $attribute) {
            $names[$aKey] = $this->getAttributeName($aKey);
        }
        return $names;
    }

    public function setOnlyStock($values)
    {
        if(isset($values->onlyStock)){
            $this->section->onlyStock = $values->onlyStock;
        }
    }

    public function getOnlyStock()
    {
        return $this->section->onlyStock;
    }

    public function getProducers()
    {
        return $this->section->producers;
    }

    public function getAttributes()
    {
        return $this->section->attributes;
    }

    public function setAttributes($attributeId, $array)
    {
        $attributes[$attributeId] = null;
        $stored = $this->section->attributes ?? [];
        if (isset($stored[$attributeId])) {
            unset($stored[$attributeId]);
        }
        if (count($array) > 0) {
            $attributes[$attributeId] = $array;
        }
        $this->section->attributes = array_replace($stored, $attributes);
    }

    public function setProducers($array)
    {
        $keys = null;
        if (count($array) > 0) {
            $keys = implode(',', $array);
        }
        $this->section->producers = $keys;
    }

    public function getPriceFrom()
    {
        if ($this->section->priceFrom) {
            return $this->section->priceFrom;
        } else {
            $this->priceFrom = $this->productRepository->getMinPrice($this->categories);
            $this->section->priceFrom = $this->priceFrom;
            return $this->priceFrom;
        }
    }

    public function getPriceTo()
    {
        if ($this->section->priceTo) {
            return $this->section->priceTo;
        } else {
            $this->priceTo = $this->productRepository->getMaxPrice($this->categories);
            $this->section->priceTo = $this->priceTo;
            return $this->priceTo;
        }
    }

    public function handleFilterReset()
    {
        unset($this->section->priceFrom);
        unset($this->section->priceTo);
        unset($this->section->producers);
        unset($this->section->attributes);
        unset($this->section->onlyStock);
        $this->onReset();
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->section->priceFrom = $values->priceFrom;
        $this->section->priceTo = $values->priceTo;
        // $this->setProducers($values->producers);
        $attributesSelect = $this->getAttributesSelect();
        foreach ($attributesSelect as $atrKey => $atrValue) {
            $atrName = 'param' . $atrKey;
            $this->setAttributes($atrKey, $values->{$atrName});
        }
        $this->setOnlyStock($values);
        $this->onDone();
    }

    public function render()
    {
        $this->template->priceFrom = $this->getPriceFrom();
        $this->template->priceTo = $this->getPriceTo();
        $this->template->minPrice = $this->productRepository->getMinPrice($this->categories);
        $this->template->maxPrice = $this->productRepository->getMaxPrice($this->categories);
        $attributesSelect = $this->getAttributesSelect();
        if (count($attributesSelect) > 0) {
            $this->template->attributes = $attributesSelect;
            $this->template->attributeNames = $this->getAttributeNames($attributesSelect);
        }
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir() . '/Product/ProductFilter/productFilter.latte');
    }
}