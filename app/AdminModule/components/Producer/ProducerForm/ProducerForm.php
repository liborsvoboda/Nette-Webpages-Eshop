<?php


namespace App\AdminModule\Components\Producer;


use App\Model\Factory\FormFactory;
use App\Model\Producer\ProducerRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class ProducerForm extends Control
{
    private $producerRepository, $formFactory;

    public $producerId, $onDone = [];

    public function __construct(ProducerRepository $producerRepository, FormFactory $formFactory)
    {
        $this->producerRepository = $producerRepository;
        $this->formFactory = $formFactory;
    }

    public function setEdit($producerId)
    {
        $this->producerId = $producerId;
    }

    public function createComponentForm()
    {
        if($this->producerId) {
            $producer = $this->producerRepository->getById($this->producerId)->fetch();
        }
        $form = $this->formFactory->create();
        $form->addText('name', 'Název');
        $form->addTextArea('description', 'Popis')->setHtmlAttribute('class', 'editor');
        $form->addUpload('image', 'Obrázek');
        if($this->producerId) {
            $form['name']->setDefaultValue($producer->name);
            $form['description']->setDefaultValue($producer->description);
        }
        $form->addSubmit('submit', 'form.save');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        if ($this->producerId) {
            $this->producerRepository->update($values, $this->producerId);
        } else {
            $producer = $this->producerRepository->add($values);
            $this->onDone($producer->id);
        }
        $this->onDone();
    }

    public function render()
    {
        if($this->producerId) {
            $producer = $this->producerRepository->getById($this->producerId)->fetch();
            $this->template->image = $producer->image;
        }
        $this->template->render(__DIR__.'/templates/producerForm.latte');
    }
}