<?php


namespace App\FrontModule\Components\Cart;


use App\Model\Factory\FormFactory;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class CartConfirm extends Control
{

    private $appSettingsService, $formFactory;

    public $onDone = [];

    public function __construct(AppSettingsService $appSettingsService, FormFactory $formFactory)
    {
        $this->appSettingsService = $appSettingsService;
        $this->formFactory = $formFactory;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addCheckbox('private')->setRequired('cart.validation.confirm_gdpr');
        $form->addCheckbox('addAccount');
        $form->addSubmit('submit');
        $form->getElementPrototype()->id = 'frm-cartConfirm';
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->onDone($values->addAccount);
    }

    public function render()
    {
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/Cart/CartConfirm/cartConfirm.latte');
    }
}