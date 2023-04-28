<?php


namespace App\AdminModule\Components\Gallery;


use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\MarketingGallery\MarketingGalleryRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class MarketingGalleryGrid extends Control
{
    private $marketingGalleryRepository, $galleryId, $gridFactory, $formFactory;

    public $onDone = [], $onImage = [], $onRemove = [], $onEdit = [];


    public $eventId = null;


    public function __construct(MarketingGalleryRepository $marketingGalleryRepository, GridFactory $gridFactory, FormFactory $formFactory)
    {
        $this->marketingGalleryRepository = $marketingGalleryRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
    }

    public function setEdit($id)
    {
        $this->galleryId = $id;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('id','ID');
        $grid->addColumnText('title','Název galerie');
        $grid->addColumnText('description','Popis');
        $grid->addColumnText('slug', 'Slug');

        $grid->addAction('edit','', 'Edit', ['id'])
            ->setIcon('edit');

        $grid->addAction('images', '', 'Images', ['id'])
            ->setIcon('images');

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
        $form->addText('title', 'Název galerie');
        $form->addTextArea('description', 'Popis')
            ->setHtmlAttribute('class', 'editor');
        $form->addText('slug', 'Slug');

        if($this->galleryId) {
            $form->addSubmit('submit', 'Upraviť');
            $values = $this->marketingGalleryRepository->getGalleryById($this->galleryId)->fetch();
            $form->setDefaults($values);
        }
        else{
            $form->addSubmit('submit', 'Přidat');
        }
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    private function getDataSource()
    {
        return $this->marketingGalleryRepository->getAllGalleries();

    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        if($this->galleryId) {
            $this->marketingGalleryRepository->updateGallery($values, $this->galleryId);
        } else {
            $this->marketingGalleryRepository->addGallery($values);
        }
        $this->onDone();
    }


    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function handleRemove ($id)
    {
        $this->marketingGalleryRepository->remove($id);
        $this['grid']->redrawControl();
    }

    public function handleImages($id)
    {
        $this->onImage($id);
    }

    public function render()
    {
        if($this->galleryId) {
            $this->template->render(__DIR__.'/templates/edit.latte');
        }
        else {
            $this->template->render(__DIR__ . '/templates/gallery.latte');
        }

    }
}