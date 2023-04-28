<?php


namespace App\AdminModule\Components\Setting;


use App\Model\Factory\FormFactory;
use App\Model\Setting\SettingRepository;
use App\Model\Page\PageRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class CompanyForm extends Control
{
    private $settingRepository, $pageRepository, $formFactory;
    public $onDone = [];

    public function __construct(SettingRepository $settingRepository, PageRepository $pageRepository, FormFactory $formFactory)
    {
        $this->settingRepository = $settingRepository;
        $this->pageRepository = $pageRepository;
        $this->formFactory = $formFactory;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();

        $form->addGroup('Firemní údaje');
        $form->addText('companyName', 'Název');
        $form->addText('companyStreet', 'Ulice');
        $form->addText('companyCity', 'Město');
        $form->addText('companyZip', 'PSČ');
        $form->addText('companyCountry', 'Stát');
        $form->addText('companyIco', 'IČO');
        $form->addText('companyDic', 'DIČ');
        $form->addText('companyIcDph', 'IČ DPH');
        $form->addText('companyContactPesron', 'Kontaktní osoba');
        $form->addText('companyContactEmail', 'Kontaktní email');
        $form->addText('companyContactPhone', 'Kontaktní telefon');
        $form->addText('companyOrderEmail', 'E-mail pro nové objednávky');
        $form->addText('companyBank', 'Banka');
        $form->addText('companyAccount', 'Číslo účtu');
        $form->addText('companyIban', 'IBAN');
        $form->addText('companySwift', 'SWIFT');

        $form->addGroup('Obecná nastavení - SK');
        $form->addTextArea('floatText', 'Text oznámení')->setHtmlAttribute('class', 'editor');
        $form->addTextArea('mainMetaDescription', 'Hlavní Meta Description')->setHtmlAttribute('rows', 6);

        $form->addGroup('Obecná nastavení - CZ');
        $form->addTextArea('floatText_cs', 'Text oznámení')->setHtmlAttribute('class', 'editor');
        $form->addTextArea('mainMetaDescription_cs', 'Hlavní Meta Description')->setHtmlAttribute('rows', 6);

        $form->addGroup('Obecná nastavení - EN');
        $form->addTextArea('floatText_en', 'Text oznámení')->setHtmlAttribute('class', 'editor');
        $form->addTextArea('mainMetaDescription_en', 'Hlavní Meta Description')->setHtmlAttribute('rows', 6);

        $form->addGroup('Ke stažení - EN');
        $form->addTextArea('downloads_en', 'Obsah stránky Ke stažení pro přihlášené uživatele')->setHtmlAttribute('class', 'editor');

        $form->addSubmit('submit', 'Uložit');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        $defaults = $this->settingRepository->getAllAsPairs();
        $form->setDefaults($defaults);
        $form->onSuccess[] = [$this, 'formSuccess'];
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
        $this->template->render(__DIR__.'/templates/companyForm.latte');
    }
}
