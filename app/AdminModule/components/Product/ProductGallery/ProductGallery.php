<?php


namespace App\AdminModule\Components\Product;


use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\ProductGallery\ProductGalleryRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class ProductGallery extends Control
{
    private $productGalleryRepository, $productId, $gridFactory, $formFactory;

    public $onDone = [];

    public function __construct(ProductGalleryRepository $productGalleryRepository, GridFactory $gridFactory, FormFactory $formFactory)
    {
        $this->productGalleryRepository = $productGalleryRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('image', 'Obrázek')
            ->setAlign('center')
            ->setTemplate(__DIR__.'/templates/image.latte');
        $grid->addColumnText('ord', 'Pořadí')->setEditableCallback([$this, 'updateOrder'])->setEditableInputType('text');
        $grid->addAction('remove', '')
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        return $grid;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addUpload('image', 'Obrázek')
            ->addRule(Form::IMAGE, 'Pouze .jpg, .png nebo .gif')->setRequired('Vyberte obrázek');
        $form->addSubmit('submit', 'Přidat');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->productGalleryRepository->add($values, $this->productId);
        $this->onDone();
    }

    public function updateOrder($id, $value)
    {
        $this->productGalleryRepository->updateOrder($id, $value);
    }

    public function handleRemove($id)
    {
        $this->productGalleryRepository->remove($id);
        $this['grid']->redraWControl();
    }

    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    private function getDataSource()
    {
        return $this->productGalleryRepository->getAllForProduct($this->productId);
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/productGallery.latte');
    }
}