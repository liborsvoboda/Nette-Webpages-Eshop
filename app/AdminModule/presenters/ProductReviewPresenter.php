<?php
declare(strict_types=1);

namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Product\IProductReviewFormFactory;
use App\AdminModule\Components\Product\IProductReviewGridFactory;
use App\AdminModule\Components\Product\ProductReviewForm;
use App\Model\Product\ProductRepository;
use Nette\SmartObject;

/**
 * Class ProductReviewPresenter
 * @package App\AdminModule\Presenters
 */
class ProductReviewPresenter extends CataloguePresenter
{
    use SmartObject;

    /**
     * @var int $reviewId
     */
    private $reviewId;

    /**
     * @var IProductReviewGridFactory @inject
     */
    public $productReviewGrid;

    /**
     * @var IProductReviewFormFactory @inject
     */
    public $productReviewForm;

    /**
     * @var ProductRepository @inject
     */
    public $productRepository;


    /**
     * @return \App\AdminModule\Components\Product\ProductReviewGrid
     */
    public function createComponentProductReviewGrid()
    {
        $grid = $this->productReviewGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        return $grid;
    }


    /**
     * @return \App\AdminModule\Components\Product\ProductReviewForm
     */
    public function createComponentProductReviewEditForm(): ProductReviewForm
    {
        $form = $this->productReviewForm->create();
        $form->setReviewId($this->reviewId);
        $form->onFormSuccess[] = function () {
            $this->flashMessage('Recenze byla úspěšně upravena!', 'success');
            if ($this->isAjax()) {
                $this->redrawControl('productReviewForm');
            } else {
                $this->redirect('default');
            }
        };

        return $form;
    }

    public function createComponentProductReviewAddForm(): ProductReviewForm
    {
        $form = $this->productReviewForm->create();
        $form->setReviewId($this->reviewId);
        $form->onFormSuccess[] = function () {
            $this->flashMessage('Recenze byla úspěšně vložena!', 'success');
            if ($this->isAjax()) {
                $this->redrawControl('productReviewForm');
            } else {
                $this->redirect('default');
            }
        };

        return $form;
    }


    /**
     * @param $id
     * @throws \Nette\Application\AbortException
     */
    public function actionEdit($id)
    {
        $this->reviewId = (int)$id;
        $review = $this->productRepository->getProductReview((int)$this->reviewId);
        if (!$review) {
            $this->redirect('ProductReview:default');
        }
    }
}
