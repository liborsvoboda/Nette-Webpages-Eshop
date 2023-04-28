<?php


namespace App\AdminModule\Components\Attribute;


use App\Model\Attribute\AttributeRepository;
use App\Model\Factory\FormFactory;
use App\Model\LocaleRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class AttributeForm extends Control
{

    private $attributeRepository, $localeRepository, $formFactory, $editId = null;
    public $onDone = [];

    public function __construct(AttributeRepository $attributeRepository, LocaleRepository $localeRepository, FormFactory $formFactory)
    {
        $this->attributeRepository = $attributeRepository;
        $this->localeRepository = $localeRepository;
        $this->formFactory = $formFactory;
    }

    public function setEdit($id)
    {
        $this->editId = $id;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $locales = $this->localeRepository->getAll()->fetchAll();
        foreach ($locales as $locale) {
            $form->addText('locale'.$locale->id, $locale->country->name)->setRequired();
        }
        $form->addCheckbox('filterable', 'Filtrovací')->setDefaultValue(true);
        $form->addCheckbox('visible', 'Viditelný')->setDefaultValue(true);
        $form->addSelect('type', 'Typ', $this->attributeRepository->getTypes());
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        $form->addSubmit('submit', 'Uložit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        if($this->editId) {
            $values = $this->attributeRepository->getById($this->editId)->fetch();
            $form->setDefaults($values);
            foreach ($locales as $locale) {
                $form['locale'.$locale->id]->setDefaultValue($this->attributeRepository->getNameById($this->editId, $locale->id));
            }
        }
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->attributeRepository->save($values, $this->editId);
        $this->onDone();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/attributeForm.latte');
    }
}