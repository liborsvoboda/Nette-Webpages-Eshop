<?php


namespace App\AdminModule\Components\Product;


use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\Product\ProductRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class ProductFeatured extends Control
{

    private $gridFactory, $productRepository, $formFactory;

    public function __construct(GridFactory $gridFactory, FormFactory $formFactory, ProductRepository $productRepository)
    {
        $this->gridFactory = $gridFactory;
        $this->productRepository = $productRepository;
        $this->formFactory = $formFactory;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('product.id');
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('image', 'Obrázek')
            ->setAlign('center')
            ->setTemplate(__DIR__.'/templates/productImage.latte');
        $grid->addColumnText('sku', 'SKU');
        $grid->addColumnText('name', 'Název');
        $grid->addAction('remove', '', '', ['id'])
            ->setIcon('trash')
            ->setTitle('Odebrat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu odebrat?')
            );
        return $grid;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addSelect('product_id', 'Produkt', $this->productRepository->getForSelect())
            ->setPrompt('---   Vyberte produkt   ---')
            ->setRequired()
            ->setHtmlAttribute('class', 'select2');
        $form->addSubmit('submit', 'Přidat');
        $form->onSuccess[] = [$this, 'formSuccess'];
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->productRepository->setFeatured($values->product_id);
        $this->redirect('this');
    }

    private function getDataSource()
    {
        return $this->productRepository->getFeatured();
    }

    public function handleRemove($id)
    {
        $this->productRepository->unsetFeatured($id);
        $this->redirect('this');
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/productFeatured.latte');
    }
}