<?php

namespace App\FrontModule\Components\AvailabilityForm;

use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\TplSettingsService;
use App\Model\Email\EmailService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;
use Nette\Database\Table\ActiveRow;

class AvailabilityForm extends Control
{

    /** @var ProductRepository */
    private $productRepository;

    /** @var AppSettingsService */
    private $appSettingsService;

    /** @var TplSettingsService */
    private $tplSettingsService;

    /** @var EmailService */
    private $emailService;

    /** @var ITranslator */
    private $translator;

    /** @var int */
    private $productId;

    /** @var bool */
    private $sent = false;

    public function __construct(
        int $productId,
        ProductRepository $productRepository,
        AppSettingsService $appSettingsService,
        TplSettingsService $tplSettingsService,
        EmailService $emailService,
        ITranslator $translator
    ) {
        $this->productId = $productId;
        $this->productRepository = $productRepository;
        $this->appSettingsService = $appSettingsService;
        $this->tplSettingsService = $tplSettingsService;
        $this->emailService = $emailService;
        $this->translator = $translator;
    }

    /**
	 * @return ActiveRow|null
	 */
	public function getProduct(): ?ActiveRow
	{
		$prods = $this->productRepository->getById($this->productId);
		return ($prods->count() <= 0) ? NULL : $prods->fetch();
	}

    public function createComponentForm()
    {
        $form = new Form();
        $form->setTranslator($this->translator);
        $form->addText('name', 'strings.contact.name');//->setRequired('form.required');
        $form->addText('surname', 'strings.contact.surname');//->setRequired('form.required');
        $form->addText('phone', 'strings.contact.phone');
        $form->addEmail('email', 'strings.contact.email')->setRequired('form.required');
        $form->addTextArea('message', 'strings.contact.message', 10, 4)->setRequired('form.required');
        $form->addSubmit('submit', 'strings.contact.send');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess($form)
    {
        $product = $this->getProduct();
        $values = $form->getValues();
        $values->productSlug = $product->slug;
        $values->productName = $product->name;
        $this->emailService->sendAvailabilityEmail($values, $this->tplSettingsService->getSetting('companyContactEmail'));
        $this->sent = true;
        $this->redrawControl('availabilityForm');
    }

    public function render()
    {
        $this->template->sent = $this->sent;
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/AvailabilityForm/availabilityForm.latte');
    }
}