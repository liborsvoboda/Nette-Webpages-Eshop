<?php


namespace App\AdminModule\Components\Setting;


use App\Model\Factory\FormFactory;
use App\Model\Setting\SettingRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class EnumForm extends Control
{

    public $onDone = [];

    private $settingRepository, $formFactory;

    public function __construct(SettingRepository $settingRepository, FormFactory $formFactory)
    {
        $this->settingRepository = $settingRepository;
        $this->formFactory = $formFactory;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addText('invoiceNumberProforma', 'Zálohová faktura');
        $form->addText('invoiceNumberRegular', 'Faktura');
        $form->addText('invoiceNumberStorno', 'Dobropis');
        $form->addSubmit('submit', 'Uložit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        $defaults = $this->settingRepository->getAllAsPairs();
        $form->setDefaults($defaults);
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->settingRepository->saveValues($values);
        $this->onDone();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/enumForm.latte');
    }
}