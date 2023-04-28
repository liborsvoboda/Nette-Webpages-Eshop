<?php


namespace App\AdminModule\Components\Locale;


use App\Model\Factory\GridFactory;
use App\Model\LocaleRepository;
use Nette\Application\UI\Control;

class LocaleGrid extends Control
{

    private $gridFactory, $localeRepository;

    public function __construct(GridFactory $gridFactory, LocaleRepository $localeRepository)
    {
        $this->gridFactory = $gridFactory;
        $this->localeRepository = $localeRepository;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('country.name', 'form.country');
        $grid->addColumnText('lang.name', 'form.language');
        $grid->addColumnText('currency.name', 'form.currency');
        return $grid;
    }

    private function getDataSource()
    {
        return $this->localeRepository->getAll();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/template/localeGrid.latte');
    }
}