<?php


namespace App\FrontModule\Components\Cart;


use App\Model\Cart\CartRepository;
use App\Model\Cart\ProductLimitException;
use App\Model\Factory\FormFactory;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Localization\ITranslator;

class CartAddForm extends Control
{
    public $product,
        $appSettingsService,
        $onDone = [],
        $onProductLimitException = [],
        $formFactory,
        $translator;

    private $cartRepository;

    public function __construct(ActiveRow $product, CartRepository $cartRepository, AppSettingsService $appSettingsService, FormFactory $formFactory, ITranslator $translator)
    {
        $this->product = $product;
        $this->cartRepository = $cartRepository;
        $this->appSettingsService = $appSettingsService;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addText('amount');
        $form->addSubmit('submit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        try {
            $this->cartRepository->addToCart($this->product->id, $values->amount);
            // $this->onDone($values->amount);
			$this->onDone($values->amount, $this->cartRepository->getItem($this->product->id));
        } catch (ProductLimitException $productLimitException) {
            $this->onProductLimitException($this->translator->translate($productLimitException->getMessage(), ['amount' => $productLimitException->getCode()]), 'warning');
        }
    }

    public function render(string $tpl = 'form')
    {
		$this->template->tpl = $tpl;
        $this->template->product = $this->product;
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir() . '/Cart/CartAddForm/cartAddForm.latte');
    }

}
