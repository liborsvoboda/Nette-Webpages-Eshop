<?php


namespace App\AdminModule\Components\Payment;


use App\Model\Factory\FormFactory;
use App\Model\LocaleRepository;
use App\Model\Payment\PaymentRepository;
use App\Model\Product\ProductRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class PaymentForm extends Control
{
    private $paymentRepository, $paymentId = null, $formFactory, $localeRepository;

    public $onDone = [];

    public function __construct(PaymentRepository $paymentRepository, FormFactory $formFactory, LocaleRepository $localeRepository)
    {
        $this->paymentRepository = $paymentRepository;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addText('name', 'Název')->setRequired();
        $form->addText('price', 'Cena')->setRequired();
        $form->addText('vat', 'DPH')->setDefaultValue(ProductRepository::BASE_DPH)->setRequired();
        $form->addSelect('locale_id', 'Země', $this->getLocales());
        $form->addSelect('type', 'Typ', $this->paymentRepository->getTypes());
        $form->addCheckbox('enabled', 'Povoleno')->setDefaultValue(1);
        $form->addSubmit('submit', 'Uložit');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        if($this->paymentId) {
            $defaults = $this->paymentRepository->getById($this->paymentId)->fetch();
            $form->setDefaults($defaults);
        }
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function setEdit($id)
    {
        $this->paymentId = $id;
    }

    private function getLocales()
    {
        return $this->localeRepository->getToSelect();
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        if($this->paymentId) {
            $this->paymentRepository->update($this->paymentId, $values);
        } else {
            $this->paymentRepository->add($values);
        }
        $this->onDone();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/paymentForm.latte');
    }

}