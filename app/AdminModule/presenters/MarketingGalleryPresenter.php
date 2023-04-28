<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Gallery\IMarketingGalleryGridFactory;
use App\AdminModule\Components\Gallery\IMarketingGalleryImageGridFactory;
use App\Model\MarketingGallery\MarketingGalleryRepository;

class MarketingGalleryPresenter extends ContentPresenter
{
    /**
     * @var IMarketingGalleryGridFactory
     * @inject
     */
    public $gallery;

    /**
     * @var IMarketingGalleryImageGridFactory
     * @inject
     */

    public $imageGallery;

    /**
     * @var MarketingGalleryRepository
     * @inject
     */

    public $galleryRepository;

    public $onEdit = [];
    public $editId = null;
    public $id;
    protected $galleryId = null;

    public function actionImages($id)
    {
        $this->galleryId = $id;
    }

    public function createComponentGalleryGrid()
    {
        $grid = $this->gallery->create();

        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        $grid->onImage[] = function ($id) {
            $this->redirect('images', $id);
        };

        $grid->onDone[] = function () {
            $this->redirect('this');
        };
        return $grid;
    }

    public function createComponentGalleryEditForm()
    {
        $form = $this->gallery->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }

    public function createComponentGalleryImageGrid()
    {
        $grid = $this->imageGallery->create();
        $grid->setGalleryId($this->galleryId);
        $grid->onDone[] = function () {
            $this->redirect('images', $this->galleryId);
        };
        return $grid;
    }

    public function actionEdit($id)
    {
        $this->editId = $id;
        $name = $this->galleryRepository->getGalleryById($id)->fetch();
        $this->template->name = $name->title;
    }
}
