<?php


namespace App\AdminModule\Components\BlogCategory;

use App\Model\Factory\FormFactory;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;
use App\Model\BlogCategory\BlogCategoryRepository;

class BlogCategoryForm extends Control {
    
    public $blogCategoryId = null,
        $onDone = [];
    
    private $blogCategoryRepository, $formFactory;

    public function __construct(BlogCategoryRepository $blogCategoryRepository, FormFactory $formFactory)
    {
        $this->blogCategoryRepository = $blogCategoryRepository;
        $this->formFactory = $formFactory;
    }
    
    public function setEdit($id)
    {
        $this->blogCategoryId = $id;
    }
    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addText('name', 'Název')->setRequired();
        $form->addText('slug', 'Slug')->setRequired();
        $form->addCheckbox('visible', 'Viditelná');
        $form->addTextArea('description', 'Popis');
        $form->addText('seoTitle', 'SEO Title');
        $form->addTextArea('seoDescription', 'SEO description');
        $form->addSubmit('submit', 'Uložit');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        if($this->blogCategoryId) {
            $values = $this->blogCategoryRepository->getById($this->blogCategoryId)->fetch();
            $form->setDefaults($values);
        }
        $form->onSuccess[] = [$this, 'succesForm'];
        return $form;
    }
    
     public function succesForm(Form $form)
    {
        $values = $form->getValues();
        
        if($this->blogCategoryId) {
            $this->blogCategoryRepository->update($values, $this->blogCategoryId);
        } else {
            $this->blogCategoryRepository->add($values);
        }
        $this->onDone();
    }
    
 public function render()
    {
        $this->template->render(__DIR__.'/templates/blogCategoryForm.latte');
    }
}
