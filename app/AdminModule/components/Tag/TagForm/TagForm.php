<?php

namespace App\AdminModule\Components\Tag;

use App\Model\Tag\TagRepository;
//use App\Model\BlogCategory\BlogCategoryRepository;
use App\Model\Factory\FormFactory;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class TagForm extends Control {

    public $tagId = null,
            $onDone = [];
    private $formFactory;
    private $tagRepository;

//
    public function __construct(FormFactory $formFactory, TagRepository $tagRepository) {
        $this->formFactory = $formFactory;
        $this->tagRepository = $tagRepository;
    }

    public function setEdit($id)
    {
        $this->tagId = $id;
    }

    public function createComponentForm() {
        $form = $this->formFactory->create();
        $form->addText('title', 'Název')->setRequired();
        $form->addText('slug', 'Slug');
        $form->addCheckbox('active', 'Aktivní');
        $form->addSubmit('submit', 'Uložit');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        if($this->tagId) {
            $values = $this->tagRepository->getById($this->tagId)->fetch();
            $form->setDefaults($values);
        }
        $form->onSuccess[] = [$this, 'succesForm'];
        return $form;
    }

    public function succesForm(Form $form) {
        $values = $form->getValues();
        if ($this->tagId) {
            $this->tagRepository->update($values, $this->tagId);
        } else {
            $this->tagRepository->add($values);
        }
        $this->onDone();
    }

    public function render() {
        $this->template->render(__DIR__ . '/templates/tagForm.latte');
    }

}
