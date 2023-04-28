<?php


namespace App\AdminModule\Components\Shipping;


use App\Model\Factory\FormFactory;
use App\Model\LocaleRepository;
use App\Model\Payment\PaymentRepository;
use App\Model\Product\ProductRepository;
use App\Model\Shipping\ShippingRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class ShippingForm extends Control
{
    private $shippingRepository, $shippingId = null, $formFactory, $localeRepository, $paymentRepository;

    public $onDone = [];

    public function __construct(ShippingRepository $shippingRepository,
                                FormFactory $formFactory,
                                LocaleRepository $localeRepository,
                                PaymentRepository $paymentRepository)
    {
        $this->shippingRepository = $shippingRepository;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function setEdit($id)
    {
        $this->shippingId = $id;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addText('name', 'Název')->setRequired();
        $form->addText('price', 'Cena')->setRequired();
        $form->addText('vat', 'DPH')->setDefaultValue(ProductRepository::BASE_DPH)->setRequired();
        $form->addSelect('locale_id', 'Země', $this->getLocales());
        $form->addSelect('type', 'Typ', $this->shippingRepository->getTypes());
        $form->addSelect('levels', 'Úrovně', $this->shippingRepository->getLevelsToSelect(1));
        $form->addMultiSelect('payments', 'Platebné metody', $this->getPaymentMethods())->setHtmlAttribute('class', 'select2');
        $form->addCheckbox('enabled', 'Povoleno')->setDefaultValue(1);
        $form->addSubmit('submit', 'Uložit');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        if($this->shippingId) {
            $defaults = $this->shippingRepository->getById($this->shippingId)->fetch();
            $form->setDefaults($defaults);
            $form['payments']->setDefaultValue($this->getSavedPayments());
        }
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        if($this->shippingId) {
            $this->shippingRepository->update($this->shippingId, $values);
        } else {
            $this->shippingRepository->add($values);
        }
        $this->onDone();
    }

    private function getLocales()
    {
        return $this->localeRepository->getToSelect();
    }

    private function getPaymentMethods()
    {
        return $this->paymentRepository->getForSelect();
    }

    private function getSavedPayments()
    {
        return $this->shippingRepository->getPaymentMethods($this->shippingId);
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/shippingForm.latte');
    }
}