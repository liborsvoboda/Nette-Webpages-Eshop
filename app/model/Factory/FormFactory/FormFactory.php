<?php


namespace App\Model\Factory;


use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;

class FormFactory
{
    public $translator;

    public function __construct(ITranslator $translator)
    {
        $this->translator = $translator;
    }

    public function create() : Form
    {
        $form = new Form();
        $form->setTranslator($this->translator);
        return $form;
    }
}