<?php


namespace App\Model\Control;


use Nette\Application\UI\Control;
use Nette\Localization\ITranslator;

abstract class BaseControl extends Control
{
    protected $translator;

    public function setTranslator(ITranslator $translator)
    {
        $this->translator = $translator;
    }
}