<?php
declare(strict_types=1);

namespace App\AdminModule\Components\Product;


use App\Model\Factory\FormFactory;
use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

/**
 * Class ProductReviewForm
 * @package App\FrontModule\Components\Product
 */
class ProductReviewForm extends Control
{
    use SmartObject;

    /**
     * @var int $reviewId
     */
    private $reviewId = null;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var callable $onFormSuccess
     */
    public $onFormSuccess;

    /**
     * @var FormFactory $formFactory
     */
    private $formFactory;


    /**
     * ProductReviewForm constructor.
     * @param int $reviewId
     * @param ProductRepository $productRepository
     * @param FormFactory $formFactory
     */
    public function __construct(ProductRepository $productRepository, FormFactory $formFactory)
    {
        $this->productRepository = $productRepository;
        $this->formFactory = $formFactory;
    }


    /**
     * @return Form
     */
    public function createComponentReviewForm(): Form
    {
        $form = $this->formFactory->create();
        if(!$this->reviewId) {
            $form->addSelect('product_id', 'Produkt', $this->productRepository->getForSelect())->setRequired();
        }
        $form->addText('reviewer_name', 'form.name')
            ->setRequired('form.valid.FILLED')
            ->addRule(Form::MAX_LENGTH, 'form.valid.MAX_LENGTH', 50);
        $form->addEmail('reviewer_email', 'form.e-mail:')
            ->setRequired('form.valid.FILLED')
            ->addRule(Form::MAX_LENGTH, 'form.valid.MIN_LENGTH', 150);
        $form->addSelect('review_star', 'form.review.label')
            ->setRequired('form.valid.FILLED')
            ->addRule(Form::INTEGER, 'form.valid.INTEGER')
            ->setItems([1, 2, 3, 4, 5], false);
        $form->addTextArea('review_text', 'form.review.text')
            ->setRequired('form.valid.FILLED')
            ->setHtmlAttribute('rows', 10);
        $form->addSelect('lang_id', 'form.review.language', $this->productRepository->getLangs()->fetchPairs('id', 'name'));
        $form->addSelect('status', 'form.review.status', [
            ProductRepository::REVIEW_VISIBLE => 'Aktivní',
            ProductRepository::REVIEW_HIDDEN => 'Neaktivní',
            ProductRepository::REVIEW_PENDING => 'Čeká na schválení'
        ]);
        $form->addSubmit('saveReview', 'form.save');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        $form->setDefaults($this->productRepository->getProductReview((int)$this->reviewId));
        $form->onSuccess[] = [$this, 'processReviewForm'];
        return $form;
    }

    public function setReviewId($reviewId)
    {
        $this->reviewId = $reviewId;
    }


    /**
     * @param Form $form
     * @param array $values
     */
    public function processReviewForm(Form $form, array $values)
    {
        if($this->reviewId){
            $this->productRepository->updateProductReview($this->reviewId, $values);
        } else {
            $status = $values['status'];
            $this->productRepository->insertProductReview($values['product_id'], $values, $status);;
        }
        $this->onFormSuccess();
    }


    /**
     * Render method
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/productReviewForm.latte');
    }
}
