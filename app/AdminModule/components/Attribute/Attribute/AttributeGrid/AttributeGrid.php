<?php


namespace App\AdminModule\Components\Attribute;


use App\Model\Attribute\AttributeRepository;
use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\LocaleRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class AttributeGrid extends Control
{
    private $attributeRepository, $gridFactory, $formFactory, $localeRepository;

    public $langId = 1, $onDone = [], $onEdit =  [], $onValuesEdit = [];

    public function __construct(AttributeRepository $attributeRepository, GridFactory $gridFactory, FormFactory $formFactory, LocaleRepository $localeRepository)
    {
        $this->attributeRepository = $attributeRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('attributeId');
        $grid->setDataSource($this->getDataSource());
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $grid->addColumnText('locale'.$locale->id, $locale->country->name)
                ->setRenderer(function ($row) use ($locale){
                    return $this->attributeRepository->getNameById($row->id, $locale->id);
                });
        }
/*
        $grid->addColumnText(':attribute_lang.name', 'Název')
            ->setEditableCallback([$this, 'updateName'])
            ->setEditableInputType('text', ['class' => 'form-control']);
*/
        $grid->addColumnText('type', 'Typ')
            ->setAlign('center')
            ->setRenderer([$this, 'getAttributeType']);
        $grid->addColumnText('filterable', 'Filtrovat')
            ->setAlign('center')
            ->setRenderer([$this, 'showFilterable']);
        $grid->addColumnText('visible', 'Viditelný')
            ->setAlign('center')
            ->setRenderer([$this, 'showVisible']);
        $grid->addAction('edit', '')
            ->setIcon('edit')
            ->setTitle('Upravit');
        $grid->addAction('valuesEdit', '')
            ->setIcon('list')
            ->setTitle('Hodnoty');
        $grid->addAction('remove', '')
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        return $grid;
    }

    public function handleValuesEdit($attributeId)
    {
        $this->onValuesEdit($attributeId);
    }

    public function handleEdit($attributeId)
    {
        $this->onEdit($attributeId);
    }

    public function handleRemove($attributeId) {
        //$this->attributeRepository->remove($attributeId);
        $this['grid']->redraWControl();
    }

    public function createComponentForm()
    {
        $form =  $this->formFactory->create();
        $form->addText('name', 'Název')->setRequired('Zadejte název');
        $form->addCheckbox('filterable', 'Filtrovací')->setDefaultValue(true);
        $form->addCheckbox('visible', 'Viditelný')->setDefaultValue(true);
        $form->addSubmit('submit', 'Přidat');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->attributeRepository->add($values->name, $values->filterable, $this->langId);
    }

    public function getDataSource()
    {
        return $this->attributeRepository->getAll();
    }

    public function updateName($id, $name)
    {
        $this->attributeRepository->updateName($id, $name);
        $this['grid']->redrawControl();
    }


    public function getAttributeType($data)
    {
        return $this->attributeRepository->getAttributeType($data->type);
    }

    public function showSearchable($data)
    {
        $out = new Html();
        if($data->searchable) {
            return $out->addHtml('<span class="text-success"><i class="fas fa-check"></i></span>');
        }
        return $out->addHtml('<span class="text-danger"><i class="fas fa-times"></i></span>');
    }

    public function showFilterable($data)
    {
        $out = new Html();
        if($data->filterable) {
            return $out->addHtml('<span class="text-success"><i class="fas fa-check"></i></span>');
        }
        return $out->addHtml('<span class="text-danger"><i class="fas fa-times"></i></span>');
    }

    public function showVisible($data)
    {
        $out = new Html();
        if($data->visible) {
            return $out->addHtml('<span class="text-success"><i class="fas fa-check"></i></span>');
        }
        return $out->addHtml('<span class="text-danger"><i class="fas fa-times"></i></span>');
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/attributeGrid.latte');
    }
}