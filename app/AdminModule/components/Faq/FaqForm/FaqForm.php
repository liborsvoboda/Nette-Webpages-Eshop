<?php


namespace App\AdminModule\Components\Faq;


use App\Model\Factory\FormFactory;
use App\Model\Faq\FaqRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class FaqForm extends Control
{
    /**
     * @var FaqRepository
     */
    private FaqRepository $faqRepository;
    /**
     * @var FormFactory
     */
    private FormFactory $formFactory;

    public $editId, $onDone = [], $localeId = 1;

    public function __construct(FaqRepository $faqRepository, FormFactory $formFactory)
    {
        $this->faqRepository = $faqRepository;
        $this->formFactory = $formFactory;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addCheckbox('active', 'Viditelné');
        $form->addTextArea('question', 'Otázka')->setHtmlAttribute('rows', 5);
        $form->addTextArea('answer', 'Odpověď')->setHtmlAttribute('rows', 10);
        $form->addSubmit('submit', 'Uložit');

        if($this->editId) {
            $form->setDefaults($this->faqRepository->getById($this->editId)->fetch());
        }
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function setLocale($id)
    {
        $this->localeId = $id;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        if($this->editId) {
            $this->faqRepository->update($this->editId, $values);
        } else {
            $values->locale_id = $this->localeId;
            $this->faqRepository->add($values);
        }
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/faqForm.latte');
    }
}