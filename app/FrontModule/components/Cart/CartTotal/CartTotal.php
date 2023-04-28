<?php


namespace App\FrontModule\Components\Cart;


use App\Model\Cart\CartRepository;
use App\Model\Factory\FormFactory;
use App\Model\Services\AppSettingsService;
use App\Model\Voucher\VoucherRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class CartTotal extends Control
{
    private $appSettingsService,
        $cartRepository,
        $formFactory,
        $couponMessage = null,
        $voucherRepository,
        $discount = null,
        $totalPrice,
        $voucherMessage = null;
    public $onDone = [], $isSummary = false;

    public function __construct(AppSettingsService $appSettingsService, CartRepository $cartRepository, VoucherRepository $voucherRepository, FormFactory $formFactory)
    {
        $this->cartRepository = $cartRepository;
        $this->appSettingsService = $appSettingsService;
        $this->voucherRepository = $voucherRepository;
        $this->formFactory = $formFactory;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addText('coupon')->setRequired('Zadejte kód kupónu');
        $form->addSubmit('submit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function setSummary()
    {
        $this->isSummary = true;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $discount = $this->voucherRepository->saveVoucher($values->coupon);

        if (!$discount) {
            $this->voucherRepository->unsetVoucher();
            $this->voucherMessage = 'Zľavový kupón nie je platný';
        }
        $this->onDone();
    }

    public function render()
    {
        $this->template->isSummary = $this->isSummary;
        $this->template->totalPrice = $this->cartRepository->getTotalPrice($this->isSummary);
        $this->discount = $this->voucherRepository->getDiscount($this->cartRepository->getComItemsPrice(false));
        if ($this->discount) {
            $this->template->discount = $this->discount;
            $this->template->totalPriceWithDiscount = $this->cartRepository->getTotalPriceWithDiscount($this->isSummary);
            $this->template->voucherCode = $this->voucherRepository->getVoucherCode();
        }
        $this->template->totalPriceWOVat = $this->cartRepository->getTotalPriceWOVat((bool)$this->discount, $this->isSummary);
        $this->template->totalVat = $this->cartRepository->getTotalVat((bool)$this->discount, $this->isSummary);
        $this->template->couponMessage = $this->voucherMessage;
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/Cart/CartTotal/cartTotal.latte');
    }
}
