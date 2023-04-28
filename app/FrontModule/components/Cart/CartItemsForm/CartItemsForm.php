<?php


namespace App\FrontModule\Components\Cart;


use App\Model\Cart\CartRepository;
use App\Model\Factory\FormFactory;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;


class CartItemsForm extends Control
{
    private $cartRepository,
        $appSettingsService,
        $formFactory,
        $cartRow;

    public $onDone = [], $isAjax = false, $isSummary = false;

    public function __construct(CartRepository $cartRepository, AppSettingsService $appSettingsService, ICartRowFactory $cartRow, FormFactory $formFactory)
    {
        $this->cartRepository = $cartRepository;
        $this->appSettingsService = $appSettingsService;
        $this->cartRow = $cartRow;
        $this->formFactory = $formFactory;
    }

    public function createComponentCartRow()
    {
        return new Multiplier(function ($productId) {
            $cartRow = $this->cartRow->create($productId);
            if($this->isSummary)  {
                $cartRow->setSummary();
            }
            $cartRow->onDone = $this->onDone;
            return $cartRow;
        });
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addSubmit('recount', 'Prepočítať')->setHtmlId('cartRecount');
        $form->onSubmit[] = [$this, 'formSubmit'];
        return $form;
    }

    public function formSubmit(Form $form)
    {
        $data = $form->getHttpData();
        foreach ($data as $key => $value) {
            if (strpos($key, 'cart-') !== false) {
                $id = str_replace('cart-', '', $key);
                $this->cartRepository->updateCartCount((int)$id, (float)$value);
            }
        }
        $this->onDone();
    }

    public function setAjax()
    {
        $this->isAjax = true;
    }

    public function setSummary()
    {
        $this->isSummary = true;
    }

    public function render()
    {
        if($this->isAjax){
            $this->template->ajax = true;
        }
        $this->template->isSummary = $this->isSummary;
        $this->template->items = $this->cartRepository->getItems();
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir() . '/Cart/CartItemsForm/cartItemsForm.latte');
    }

}