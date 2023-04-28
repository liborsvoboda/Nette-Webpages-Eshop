<?php


namespace App\AdminModule\Components\Gallery;


use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\Gallery\MarketingGalleryRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class GalleryImageGrid extends Control
{
    private $galleryRepository, $galleryId, $gridFactory, $formFactory;

    public $onDone = [], $onEdit = [], $onRemove = [];

    public function __construct(MarketingGalleryRepository $galleryRepository, GridFactory $gridFactory, FormFactory $formFactory)
    {
        $this->galleryRepository = $galleryRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
    }

    public function setGalleryId($id)
    {
        $this->galleryId = $id;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('image','Obrázek')
            ->setAlign('center')
            ->setTemplate(__DIR__.'/templates/image.latte');
        
        $grid->addAction('remove','', 'Remove', ['id'])
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        return $grid;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addUpload('image', 'Obrázek')
            ->addRule(Form::IMAGE, 'Pouze formáty obrázku')->setRequired('Vyberte obrázek');
        $form->addSubmit('submit', 'Přidat');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    private function getDataSource()
    {
        return $this->galleryRepository->getAll($this->galleryId);

    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->galleryRepository->add($values, $this->galleryId);
        $this->onDone();
    }



    public function handleRemove ($id)
    {
        $this->galleryRepository->remove($id);
        $this['grid']->redraWControl();
    }

    
    public function render()
    {
        $this->template->render(__DIR__.'/templates/grid.latte');
    }
}