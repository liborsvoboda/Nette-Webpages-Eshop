<?php

namespace App\AdminModule\Components\Shipping;

use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\Setting\SettingRepository;
use App\Model\Shipping\ShippingRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class ShippingGrid extends Control
{

    private $shippingRepository, $shippingId, $settingRepository, $gridFactory, $formFactory;

    public $onEdit = [], $onDone = [], $onLevelsEdit = [];

    public function __construct(ShippingRepository $shippingRepository, SettingRepository $settingRepository, GridFactory $gridFactory, FormFactory $formFactory)
    {
        $this->shippingRepository = $shippingRepository;
        $this->settingRepository = $settingRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('name', 'Název');
        $grid->addColumnText('price', 'Cena');
        $grid->addColumnText('locale', 'Země')
            ->setRenderer(function ($row){
                return $row->locale->country->name;
            });
        $grid->addColumnText('enabled', 'Stav')
            ->setAlign('center')
            ->setRenderer([$this, 'showActive']);
        $grid->addAction('edit','', 'Edit', ['id'])
            ->setIcon('edit');
        $grid->addAction('levels', '', 'Levels', ['id'])
            ->setIcon('layer-group');
        return $grid;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addCheckbox('freeDeliveryEnabled', 'Povolit dopravu zdarma');
        $form->addCheckbox('freeDeliveryShow', 'Zobrazovat dopravu zdarma');
        $form->addText('freeDeliverySK', 'Doprava zdarma od SK');
        $form->addText('freeDeliveryCZ', 'Doprava zdarma od CZ');
        $form->addSubmit('submit', 'Uložit');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        $defaults = $this->settingRepository->getAll()->fetchPairs('key', 'value');
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

    public function handleLevels($id)
    {
        $this->onLevelsEdit($id);
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function showActive($data)
    {
        $out = new Html();
        if($data->enabled) {
            return $out->addHtml('<span class="text-success"><i class="fas fa-check"></i></span>');
        }
        return $out->addHtml('<span class="text-danger"><i class="fas fa-times"></i></span>');
    }


    private function getDataSource()
    {
        return $this->shippingRepository->getAll();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/shippingGrid.latte');
    }

}