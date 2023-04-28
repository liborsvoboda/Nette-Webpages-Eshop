<?php

namespace App\AdminModule\Components\Menu;

use App\Model\Control\BaseControl;
use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\LocaleRepository;
use App\Model\Menu\MenuRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class MenuGrid extends Control
{
    private $menuRepository, $gridFactory, $formFactory, $localeRepository, $langId = 1;

    public $onEdit = [];

    public function __construct(MenuRepository $menuRepository,
                                GridFactory $gridFactory,
                                FormFactory $formFactory,
                                LocaleRepository $localeRepository)
    {
        $this->menuRepository = $menuRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('menu.id');
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('title', 'Název');
        $grid->addColumnText('slug', 'Slug');
        $grid->addColumnText('position', 'Pozice')
            ->setRenderer([$this, 'showPosition']);
		$grid->addColumnText('sort', 'Poradie');
        $grid->addAction('edit', '', 'Edit', ['id'])
            ->setIcon('edit');
        $grid->addAction('remove', '', 'Remove', ['id'])
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        return $grid;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addSelect('lang_id', 'form.language', $this->localeRepository->getLangsToSelect())->setDefaultValue($this->langId);
        $form->addSubmit('submit', 'form.change');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->langId = $values->lang_id;
        $this['grid']->redrawControl();
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function showPosition($data)
    {
        return MenuRepository::POSITION[$data->position];
    }

    public function handleRemove($id)
    {
        $this->menuRepository->remove($id);
        $this['grid']->redraWControl();
    }


    public function handleSort()
    {

    }

    public function getDataSource()
    {
        return $this->menuRepository->getAll($this->langId);
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/menuGrid.latte');
    }
}