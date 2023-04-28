<?php
declare(strict_types=1);


namespace App\AdminModule\Components\Product;

use App\Model\Factory\GridFactory;
use App\Model\Product\ProductRepository;
use Nette\Application\UI\Control;
use Nette\Utils\Html;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;


class ProductReviewGrid extends Control
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /** @var GridFactory */
    private $gridFactory;

    /**
     * @var array
     */
    public $onEdit = [];


    /**
     * ProductReviewGrid constructor.
     * @param ProductRepository $productRepository
     * @param GridFactory $gridFactory
     */
    public function __construct(ProductRepository $productRepository, GridFactory $gridFactory)
    {
        $this->productRepository = $productRepository;
        $this->gridFactory = $gridFactory;
    }


    /**
     * @return DataGrid
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('id');
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('name', 'Produkt');
        $grid->addColumnText('reviewer_name', 'Jméno');
        $grid->addColumnText('reviewer_email', 'E-mail');
        $grid->addColumnText('review_star', 'Hodnocení');
        $grid->addColumnText('status', 'Status')
            ->setAlign('center')
            ->setRenderer(function ($review) {
                $out = new Html();
                if ($review->status == ProductRepository::REVIEW_VISIBLE) {
                    $out->addHtml('<span class="badge badge-success">Aktivní</span>');
                } elseif ($review->status == ProductRepository::REVIEW_PENDING) {
                    $out->addHtml('<span class="badge badge-warning">Čeká na schválení</span>');
                } else {
                    $out->addHtml('<span class="badge badge-primary">Neaktivní</span>');
                }
                return $out;
            });
        $grid->addAction('allow', '', 'Allow', ['id'])
            ->setRenderCondition(function ($review) {
                return $review->status == ProductRepository::REVIEW_PENDING;
            })
            ->setTitle('Schválit')
            ->setClass('btn btn-xs btn-success')
            ->setIcon('check');
        $grid->addAction('edit', '', 'Edit', ['id'])
            ->setTitle('Upravit')
            ->setClass('btn btn-xs btn-primary')
            ->setIcon('edit');
        $grid->addAction('remove', '', 'Remove', ['id'])
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        $grid->addFilterText('name', 'Hledat', ['name', 'reviewer_email', 'reviewer_name']);
        $grid->setOuterFilterRendering();
        return $grid;
    }


    /**
     * @param $id
     */
    public function handleEdit($id)
    {
        $this->onEdit((int)$id);
    }


    /**
     * @param $id
     * @throws \Nette\Application\AbortException
     */
    public function handleAllow($id)
    {
        $this->productRepository->setReviewStatus((int)$id, ProductRepository::REVIEW_VISIBLE);
        $this->redirect('this');
    }


    /**
     * @param $id
     * @throws \Nette\Application\AbortException
     */
    public function handleRemove($id)
    {
        $this->productRepository->setReviewStatus((int)$id, ProductRepository::REVIEW_HIDDEN);
        $this->redirect('this');
    }


    /**
     * @return array|\Nette\Database\Table\IRow[]
     */
    private function getDataSource()
    {
        return $this->productRepository->getAllProductReviews();
    }


    /**
     * Render method
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/productReviewGrid.latte');
    }
}
