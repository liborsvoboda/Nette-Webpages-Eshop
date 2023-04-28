<?php

namespace App\AdminModule\Components\Profit;

use App\Model\Factory\GridFactory;
use App\Model\Profit\ProfitRepository;
use Nette\Application\UI\Control;
use Nette\Forms\Container;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class ProfitGrid extends Control
{

    private $profitRepository, $gridFactory;

    public function __construct(ProfitRepository $profitRepository, GridFactory $gridFactory)
    {
        $this->profitRepository = $profitRepository;
        $this->gridFactory = $gridFactory;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('priceFrom', 'Cena od')->setEditableCallback([$this, 'updatePriceFrom'])->setEditableInputType('text');
        $grid->addColumnText('priceTo', 'Cena do')->setEditableCallback([$this, 'updatePriceTo'])->setEditableInputType('text');
        $grid->addColumnText('profit', 'Marže (%)')->setEditableCallback([$this, 'updateProfit'])->setEditableInputType('text');
        $grid->addAction('remove', '')
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        $grid->addInlineAdd()
            ->setPositionTop()
            ->onControlAdd[] = function (Container $container) {
            $container->addText('priceFrom', '')->addRule(Form::FLOAT, 'Musí být číslo')->setRequired();
            $container->addText('priceTo', '')->addRule(Form::FLOAT, 'Musí být číslo')->setRequired();
            $container->addText('profit', '')->addRule(Form::FLOAT, 'Musí být číslo')->setRequired();
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'addLine'];
        return $grid;
    }

    public function handleRemove($id)
    {
        $this->profitRepository->remove($id);
        $this['grid']->redraWControl();
    }

    public function addLine(ArrayHash $values)
    {
        $this->profitRepository->add($values);
    }

    public function updatePriceFrom($id, $value)
    {
        if (is_numeric($value)) {
            $this->profitRepository->update($id, ['priceFrom' => $value]);
        }
        $this['grid']->redrawControl();
    }

    public function updatePriceTo($id, $value)
    {
        if (is_numeric($value)) {
            $this->profitRepository->update($id, ['priceTo' => $value]);
        }
        $this['grid']->redrawControl();
    }

    public function updateProfit($id, $value)
    {
        if (is_numeric($value)) {
            $this->profitRepository->update($id, ['profit' => $value]);
        }
        $this['grid']->redrawControl();
    }

    private function getDataSource()
    {
        return $this->profitRepository->getAll();
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/profitGrid.latte');
    }
}