<?php


namespace App\AdminModule\Components\Admin;


use App\Model\Factory\FormFactory;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class DatePickForm extends Control
{

    private $formFactory, $dateFrom, $dateTo;

    public $onDone = [];

    public function __construct(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
        $this->dateFrom = new DateTime('first day of this month');
        $this->dateFrom->setTime(0,0,0);
        $this->dateTo = new DateTime('last day of this month');
        $this->dateTo->setTime(23,59,59);
    }

    public function setFrom($from)
    {
        if($from) {
            $this->dateFrom = DateTime::createFromFormat('Y-m-d', $from);
            $this->dateFrom->setTime(0,0,0);
        }
    }

    public function getFrom()
    {
        return $this->dateFrom->format('Y-m-d');
    }

    public function getTo()
    {
        return $this->dateTo->format('Y-m-d');
    }

    public function setTo($to)
    {
        if($to) {
            $this->dateTo = DateTime::createFromFormat('Y-m-d', $to);
            $this->dateTo->setTime(23,59,59);
        }
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addText('dateFrom', 'Od')->setDefaultValue($this->dateFrom->format('d.m.Y'));
        $form->addText('dateTo', 'Do')->setDefaultValue($this->dateTo->format('d.m.Y'));
        $form->addSubmit('submit', 'Nastaviť');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $dateFrom = DateTime::createFromFormat('d.m.Y', $values->dateFrom);
        $dateFrom->setTime(0, 0);
        $dateTo = DateTime::createFromFormat('d.m.Y', $values->dateTo);
        $dateTo->setTime(23, 59, 59);
        $this->onDone($dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d'));
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/datePick.latte');
    }
}