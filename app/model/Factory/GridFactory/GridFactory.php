<?php


namespace App\Model\Factory;


use Nette\Localization\ITranslator;
use Ublaboo\DataGrid\DataGrid;

class GridFactory
{
    public $translator;

    public function __construct(ITranslator $translator)
    {
        $this->translator = $translator;
    }

    public function create()
    {
        $grid = new DataGrid();
        $grid->setTranslator($this->translator);
        return $grid;
    }
}