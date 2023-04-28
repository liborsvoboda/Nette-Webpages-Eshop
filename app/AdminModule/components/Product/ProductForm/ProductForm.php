<?php

namespace App\AdminModule\Components\Product;

use App\Model\Attribute\AttributeRepository;
use App\Model\Category\CategoryRepository;
use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\LocaleRepository;
use App\Model\Producer\ProducerRepository;
use App\Model\Product\PriceFacade;
use App\Model\Product\ProductRepository;
use App\Model\Services\TplSettingsService;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class ProductForm extends Control
{
    private $productRepository,
        $categoryRepository,
        $attributeRepository,
        $producerRepository,
        $product,
        $productAttributes,
        $tplSettingService,
        $userLevelRepository,
        $localeRepository,
        $priceFacade,
        $gridFactory,
        $formFactory;

    public $productId = null,
        $onDone = [];

    public function __construct(
        ProductRepository   $productRepository,
        CategoryRepository  $categoryRepository,
        AttributeRepository $attributeRepository,
        ProducerRepository  $producerRepository,
        TplSettingsService  $tplSettingService,
        UserLevelRepository $userLevelRepository,
        FormFactory         $formFactory,
        GridFactory         $gridFactory,
        LocaleRepository    $localeRepository,
        PriceFacade         $priceFacade
    )
    {

        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->attributeRepository = $attributeRepository;
        $this->producerRepository = $producerRepository;
        $this->tplSettingService = $tplSettingService;
        $this->formFactory = $formFactory;
        $this->userLevelRepository = $userLevelRepository;
        $this->localeRepository = $localeRepository;
        $this->priceFacade = $priceFacade;
        $this->gridFactory = $gridFactory;
    }

    public function setEdit($productId)
    {
        $this->productId = $productId;
        $this->product = $this->productRepository->getById($this->productId)->fetch();
        $this->productAttributes = $this->attributeRepository->getProductAttributes($this->productId);

    }

    public function createComponentForm()
    {

        $form = $this->formFactory->create();
        $form->addCheckbox('new_tag', 'form.product.new_tag');
        $form->addCheckbox('active', 'form.product.active')->setDefaultValue(true);
        $form->addCheckbox('featured', 'form.product.featured');
        $form->addCheckbox('sale_tag', 'form.product.sale_tag');
        $form->addCheckbox('tip_tag', 'form.product.tip_tag');
        $form->addCheckbox('not_for_sale', 'form.product.not_for_sale');
        $form->addCheckbox('shop_only', 'form.product.shop_only');
        $form->addCheckbox('inStock', 'form.inStock');
        $form->addText('sku', 'form.sku')->setNullable();
        $form->addText('ean', 'form.ean');
        $form->addUpload('image', 'form.product.image')
            ->addRule(Form::IMAGE, 'form.product.image_formats');
        $form->addSelect('producer_id', 'form.manufacturer', $this->producerRepository->getForSelect());
        $form->addText('unit', 'form.unit');
        $form->addCheckbox('commission', 'form.product.commission')->setDefaultValue(true);
        $form->addInteger('comProductAmount', 'form.product.comProductAmount')->setDefaultValue(1);
        //$form->addCheckbox('gift', 'form.product.gift');
        $form->addText('order_min', 'form.group_order.min')->setNullable();
        $form->addText('order_max', 'form.group_order.max')->setNullable();
        $form->addSelect('category_id', 'form.main_category', $this->categoryRepository->getFullPathToSelect())->setRequired();
        $form->addMultiSelect('multiCat', 'form.other_categories', $this->categoryRepository->getFullPathToSelect());
        $form->addInteger('sort', 'form.sort')->setDefaultValue(1)->setRequired();

//        $form->addCheckbox('is_combo', 'form.is_combo');
//        $form->addMultiSelect('combo_products', 'form.combo_products', $this->productRepository->getForSelect());

        $attributes = $this->attributeRepository->getAllToSelect();
        foreach ($attributes as $key => $attribute) {
            $attrValues = $this->attributeRepository->getValuesToSelect($key, 1);
            $form->addMultiSelect('attr' . $key, $attribute, $attrValues)->setHtmlAttribute('class', 'select2 form-control');
        }

        $groupLevels = $this->userLevelRepository->getAllGroups();
        $userLevels = $this->userLevelRepository->getAll()->fetchAll();

        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $form->addContainer('locale' . $locale->id);
            $form['locale' . $locale->id]->addText('name', 'form.title')
                ->setRequired('form.valid.FILLED');
            $form['locale' . $locale->id]->addTextArea('perex', 'form.perex')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addTextArea('description', 'form.description')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addTextArea('description2', 'form.description2')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addTextArea('ingredients', 'form.product.ingredients')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addTextArea('nutritional', 'form.product.nutritional')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addTextArea('warnings', 'form.product.warnings')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addTextArea('faq', 'form.product.faq')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addTextArea('taking', 'form.product.taking')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addTextArea('benefits', 'form.product.benefits')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addTextArea('below_button', 'form.product.below_button')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addText('sale_text', 'form.sale_text');
            $form['locale' . $locale->id]->addText('slug', 'form.slug');
            $form['locale' . $locale->id]->addText('orig_price_vat', "Pôvodna cena")->setNullable();
            foreach ($groupLevels as $groupLevel) {
                $form['locale' . $locale->id]->addText('price' . $groupLevel->id, $groupLevel->name)->setRequired('Chybí cena');
            }
            $form['locale' . $locale->id]->addText('vat' . $locale->id, 'form.vat');
            $commCont = $form['locale' . $locale->id]->addContainer('commissions');
            $userLevelsHorizontal = array_reverse($userLevels);
            foreach ($userLevels as $vertical) {
                if ($vertical->user_group_id == 1) {
                    continue;
                }
                foreach ($userLevelsHorizontal as $horizontal) {
                    if ($horizontal >= $vertical) {
                        continue;
                    }
                    if ($horizontal->user_group_id == 1) {
                        continue;
                    }
                    $commCont->addText('commission' . $vertical . 'h' . $horizontal, '')
                        ->addConditionOn($form['commission'], $form::EQUAL, true)
                        ->setRequired('Chybí provízia');
                }
            }
        }
        if ($this->productId) {
            $defaults = $this->product->toArray();
            unset($defaults['combo_products']);
            unset($defaults['image']);
            $form->setDefaults($defaults);
            $form['multiCat']->setDefaultValue($this->categoryRepository->getMultiCat($this->productId));
//            $form['combo_products']->setDefaultValue(json_decode($this->product->combo_products, true));
            $attributes = $this->attributeRepository->getAllToSelect();
            foreach ($attributes as $key => $attribute) {
                $defValues = $this->attributeRepository->getProductAttributeValues($this->productId, $key);
                $form['attr' . $key]->setDefaultValue($defValues);
            }

            foreach ($locales as $locale) {
                $langItems = $this->productRepository->getLangItems($this->productId, $locale->lang->id)->fetch();
                if ($langItems) {
                    $form['locale' . $locale->id]['perex']->setDefaultValue($langItems->perex);
                    $form['locale' . $locale->id]['description']->setDefaultValue($langItems->description);
                    $form['locale' . $locale->id]['description2']->setDefaultValue($langItems->description2);
                    $form['locale' . $locale->id]['ingredients']->setDefaultValue($langItems->ingredients);
                    $form['locale' . $locale->id]['nutritional']->setDefaultValue($langItems->nutritional);
                    $form['locale' . $locale->id]['warnings']->setDefaultValue($langItems->warnings);
                    $form['locale' . $locale->id]['faq']->setDefaultValue($langItems->faq);
                    $form['locale' . $locale->id]['taking']->setDefaultValue($langItems->taking);
                    $form['locale' . $locale->id]['benefits']->setDefaultValue($langItems->benefits);
                    $form['locale' . $locale->id]['sale_text']->setDefaultValue($langItems->sale_text);
                    $form['locale' . $locale->id]['name']->setDefaultValue($langItems->name);
                    $form['locale' . $locale->id]['slug']->setDefaultValue($langItems->slug)->setHtmlAttribute('readonly', 'readonly');

                }
                $priceItems = $this->productRepository->getPriceItems($this->productId, $locale->id);
                $originalPrice = $this->productRepository->getOriginalPrice($this->productId, $locale->id);

                if ($priceItems) {
                    foreach ($groupLevels as $groupLevel) {
                        $form['locale' . $locale->id]['price' . $groupLevel->id]->setDefaultValue($priceItems[$groupLevel->id]);
                    }
                }
                if ( $originalPrice) {
                    foreach ($groupLevels as $groupLevel) {
                        $form['locale' . $locale->id]['orig_price_vat']->setDefaultValue($originalPrice[$groupLevel->id]);
                    }
                }


                $vat = $this->priceFacade->getVat($this->productId, $locale->id);
                $form['locale' . $locale->id]['vat' . $locale->id]->setDefaultValue($vat);
                if ($this->product->commission
                ) {
                    $commissions = json_decode($langItems->commissions, true);
                    $commissionsHorizontal = array_reverse($commissions);
                    foreach ($commissions as $kvert => $vertical) {
                        foreach ($commissionsHorizontal as $khoriz => $horizontal) {
                            if (isset($commissions[$kvert][$khoriz])) {
                                $form['locale' . $locale->id]['commissions']['commission' . $kvert . 'h' . $khoriz]->setDefaultValue($commissions[$kvert][$khoriz]);
                            }
                        }
                    }
                }
                /*
                foreach ($userLevels as $userLevel) {
                    if(isset($commissions[$userLevel->id])) {
                        $form['locale'.$locale->id]['commission'.$userLevel->id]->setDefaultValue($commissions[$userLevel->id]);
                    }
                }
                */
            }
        }
        $form->addSubmit('submit', 'form.save');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        if ($this->productId) {
            $this->productRepository->update($values, $this->productId);
        } else {
            $product = $this->productRepository->add($values);
            $this->productId = $product->id;
        }
        $this->onDone($this->productId);
    }

    public function createComponentComboGrid()
    {
        $grid = $this->gridFactory->create();
        $products = $this->productRepository->getForSelect();
        $grid->setDataSource($this->productRepository->getComboProducts($this->productId));
        $grid->addColumnText('product_id', 'Produkt')
            ->setRenderer(function ($row) use ($products){
                return $products[$row->combo_id];
            });
        $grid->addColumnText('amount', 'Počet ks');
        $grid->addAction('remove', '', 'removeComboProduct', ['id'])
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        return $grid;
    }

    public function handleRemoveComboProduct($id)
    {
        $this->productRepository->removeComboProduct($id);
        $this->redrawControl('comboGrid');
    }

    public function createComponentComboForm()
    {
        $form = $this->formFactory->create();
        $form->addSelect('product_id', 'Produkt', $this->productRepository->getForSelect());
        $form->addText('amount', 'Počet ks')->setHtmlType('number')->setHtmlAttribute('min', 1)->setRequired();
        $form->addSubmit('submit', 'Pridať');
        $form->onSuccess[] = [$this, 'comboFormSuccess'];
        return $form;
    }

    public function comboFormSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->productRepository->addComboProduct($this->productId, $values);
        $this->redrawControl('comboGrid');
    }


    public function render()
    {
        if ($this->productId) {
            $this->template->mainImage = $this->product->image;
        }
        $this->template->locales = $this->localeRepository->getAll();
        $this->template->productId = $this->productId;
        $this->template->tplSetting = $this->tplSettingService;
        $this->template->attributes = $this->attributeRepository->getAll();
        $this->template->groupLevels = $this->userLevelRepository->getAllGroups();
        $this->template->userLevels = $this->userLevelRepository->getAll()->fetchAll();
        $this->template->setFile(__DIR__ . '/templates/productForm.latte');
        $this->template->render();
    }
}