<?php

namespace App\FrontModule\Components\ContactForm;

use App\Model\Services\AppSettingsService;
use App\Model\Services\TplSettingsService;
use App\Model\Email\EmailService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;

class ContactForm extends Control
{

    /** @var AppSettingsService */
    private $appSettingsService;

    /** @var TplSettingsService */
    private $tplSettingsService;

    /** @var EmailService */
    private $emailService;

    /** @var ITranslator */
    private $translator;

    /** @var bool */
    private $sent = false;

    public function __construct(
        AppSettingsService $appSettingsService,
        TplSettingsService $tplSettingsService,
        EmailService $emailService,
        ITranslator $translator
    ) {
        $this->appSettingsService = $appSettingsService;
        $this->tplSettingsService = $tplSettingsService;
        $this->emailService = $emailService;
        $this->translator = $translator;
    }

    public function createComponentForm()
    {
        $form = new Form();
        $form->setTranslator($this->translator);
        $form->addText('name', 'strings.contact.name')->setRequired('form.required');
        $form->addText('surname', 'strings.contact.surname')->setRequired('form.required');
        $form->addText('phone', 'strings.contact.phone');
        $form->addSelect('country', 'strings.contact.country', [
            'Slovensko' => 'Slovensko',
            'Česko' => 'Česko'
        ]);
        $form->addEmail('email', 'strings.contact.email')->setRequired('form.required');
        $form->addTextArea('message', 'strings.contact.message', 10, 4)->setRequired('form.required');
        $form->addSubmit('submit', 'strings.contact.send');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess($form)
    {
        $values = $form->getValues();
        $this->emailService->sendContactEmail($values, $this->tplSettingsService->getSetting('companyContactEmail'));
        $this->sent = true;
        $this->redrawControl('contactForm');
    }

    public function render()
    {
        $this->template->sent = $this->sent;
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/ContactForm/contactForm.latte');
    }
}