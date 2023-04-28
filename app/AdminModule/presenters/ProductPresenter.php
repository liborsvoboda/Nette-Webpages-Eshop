<?php


namespace App\AdminModule\Presenters;

use App\AdminModule\Components\Product\IProductFeaturedFactory;
use App\AdminModule\Components\Product\IProductFormFactory;
use App\AdminModule\Components\Product\IProductGalleryFactory;
use App\AdminModule\Components\Product\IProductGridFactory;
use App\Model\Feed\FeedRepository;
use App\Model\Fhb\FhbService;

class ProductPresenter extends CataloguePresenter
{
    /**
     * @var IProductGridFactory
     * @inject
     */
    public $productGrid;

    /**
     * @var IProductFormFactory
     * @inject
     */
    public $productForm;

    /**
     * @var IProductGalleryFactory
     * @inject
     */
    public $productGallery;

    /**
     * @var IProductFeaturedFactory
     * @inject
     */
    public $productFeaturedFactory;

    /**
     * @var FhbService
     * @inject
     */
    public $fhbService;

    /**
     * @var FeedRepository
     * @inject
     */
    public $feedRepository;

    protected $editId = null, $galleryId = null;

    public function actionEdit($id)
    {
        $this->editId = $id;
    }

    public function actionEditGallery($id)
    {
        $this->galleryId = $id;
    }

    public function createComponentProductGrid()
    {
        $grid = $this->productGrid->create();
        $grid->onEdit[] = function ($product_id) {
            $this->redirect('edit', $product_id);
        };
        $grid->onEditGallery[] = function ($product_id) {
            $this->redirect('editGallery', $product_id);
        };
        return $grid;
    }

    public function createComponentProductAddForm()
    {
        $form = $this->productForm->create();
        $form->onDone[] = function ($id) {
            $this->fhbService->sendProduct($id, false);
            $this->feedRepository->makeGoogleAll();
            $this->redirect('edit', $id);
        };
        return $form;
    }

    public function createComponentProductEditForm()
    {
        $form = $this->productForm->create();
        $form->setEdit($this->editId);
        $form->onDone[] = function ($id) {
            $this->fhbService->sendProduct($id);
            $this->feedRepository->makeGoogleAll();
            $this->redirect('this');
        };
        return $form;
    }

    public function createComponentGalleryEdit()
    {
        $grid = $this->productGallery->create();
        $grid->setProductId($this->galleryId);
        $grid->onDone[] = function () {
            $this->redirect('this');
        };
        return $grid;
    }

    public function createComponentProductFeaturedGrid()
    {
        $grid = $this->productFeaturedFactory->create();
        return $grid;
    }
}
