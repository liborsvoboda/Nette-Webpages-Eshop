<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Locale\ILocaleGridFactory;

class LocalePresenter extends SettingPresenter
{
    /**
     * @var ILocaleGridFactory
     * @inject
     */
    public $localeGrid;

    public function createComponentLocaleGrid()
    {
        $grid = $this->localeGrid->create();
        return $grid;
    }
}