<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Gallery\IMarketingGalleryGridFactory;
use App\AdminModule\Components\Gallery\IMarketingGalleryImageGridFactory;

class GalleryPresenter extends ContentPresenter
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

    protected $galleryId = null;

    public function actionImages($id)
    {
        $this->galleryId = $id;
    }

    

    public function createComponentGalleryGrid()
    {
        $grid = $this->gallery->create();
        //$grid->setGalleryId($this->galleryId);
        $grid->onImage[] = function ($id) {
            $this->redirect('images', $id);
        };

        $grid->onDone[] = function() {
            $this->redirect('this');
        };
        return $grid;
    }

    public function createComponentGalleryImageGrid()
    {
        $grid = $this->imageGallery->create();
        $grid->setGalleryId($this->galleryId);
        $grid->onDone[] = function () {
            $this->redirect('images',$this->galleryId);
        };
        return $grid;
    }
    

}