<?php
declare(strict_types=1);

namespace App\FrontModule\Components\Product;


use App\Model\Factory\FormFactory;
use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\SmartObject;

/**
 * Class ProductReviewForm
 * @package App\FrontModule\Components\Product
 */
class ProductReviewForm extends Control
{
    use SmartObject;

    /**
     * @var int $productId
     */
    private $productId;

    /**
     * @var AppSettingsService
     */
    private $appSettingsService;

    /**
     * @var ProductRepository
     */
    private $productRepository;


    private $formFactory;

    /**
     * @var callable $onFormSuccess
     */
    public $onFormSuccess;


    /**
     * ProductReviewForm constructor.
     * @param int $productId
     * @param AppSettingsService $appSettingsService
     * @param ProductRepository $productRepository
     * @param FormFactory $formFactory
     */
    public function __construct(int $productId, AppSettingsService $appSettingsService, ProductRepository $productRepository, FormFactory $formFactory)
    {
        $this->productId = $productId;
        $this->appSettingsService = $appSettingsService;
        $this->productRepository = $productRepository;
        $this->formFactory = $formFactory;
    }


    /**
     * @return Form
     */
    public function createComponentReviewForm(): Form
    {
        $form = $this->formFactory->create();
        $form->addText('reviewer_name', 'cart.address.name')
            ->setRequired('form.valid.FILLED')
            ->addRule(Form::MAX_LENGTH, 'form.valid.MAX_LENGTH', 50);
        $form->addEmail('reviewer_email', 'form.label.email')
            ->setRequired('form.valid.FILLED')
            ->addRule(Form::MAX_LENGTH, 'form.valid.MAX_LENGTH', 150);
        $form->addRadioList('review_star', 'form.label.evaluation')
            ->setRequired('form.valid.FILLED')
            ->addRule(Form::INTEGER, 'form.valid.INTEGER')
            ->setItems([1, 2, 3, 4, 5], false);
        $form->addTextArea('review_text', 'form.label.message')
            ->setRequired('form.valid.FILLED')
            ->addRule(Form::MAX_LENGTH, 'form.valid.MAX_LENGTH', 2000);
        $form->addSubmit('sendReview', 'form.label.send');
        $form->onSuccess[] = [$this, 'processReviewForm'];
        return $form;
    }


    /**
     * @param Form $form
     * @param array $values
     */
    public function processReviewForm(Form $form, array $values)
    {
        $result = $this->productRepository->insertProductReview($this->productId, $values);
        if (!$result) {
            $form->addError('form.message.error');
            return;
        }

        $this->onFormSuccess();
    }


    /**
     * Render method
     */
    public function render()
    {
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir() . '/Product/ProductReview/productReview.latte');
    }
}
