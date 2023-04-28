<?php


namespace App\AdminModule\Components\Page;


use App\Model\Factory\FormFactory;
use App\Model\LocaleRepository;
use App\Model\Page\PageRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class PageForm extends Control
{
    public $pageId = null,
        $onDone = [];

    private $pageRepository, $formFactory, $localeRepository;

    public function __construct(PageRepository $pageRepository, FormFactory $formFactory, LocaleRepository $localeRepository)
    {
        $this->pageRepository = $pageRepository;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
    }

    public function setEdit($id)
    {
        $this->pageId = $id;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addCheckbox('active', 'Aktivní');
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $form->addContainer('locale' . $locale->id);
            $form['locale' . $locale->id]->addContainer('locale' . $locale->id);
            $form['locale' . $locale->id]->addText('title', 'Název')->setRequired();
            $form['locale' . $locale->id]->addTextArea('text', 'Text')->setHtmlAttribute('class', 'editor')->setRequired();
            $form['locale' . $locale->id]->addText('slug', 'Slug');
        }
        $form->addSubmit('submit', 'Uložit');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        if ($this->pageId) {
            $values = $this->pageRepository->getById($this->pageId)->fetch();
            $form['active']->setDefaultValue($values->active);
            foreach ($locales as $locale) {
                $langItems = $this->pageRepository->getLangItems($this->pageId, $locale->lang->id)->fetch();
                @$form['locale' . $locale->id]['title']->setDefaultValue($langItems->title);
                @$form['locale' . $locale->id]['text']->setDefaultValue($langItems->text);
                @$form['locale' . $locale->id]['slug']->setDefaultValue($langItems->slug)->setHtmlAttribute('readonly', 'readonly');
            }
        }
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->pageRepository->save($values, $this->pageId);
        $this->onDone();
    }

    public function render()
    {
        $this->template->locales = $this->localeRepository->getAll();
        $this->template->render(__DIR__ . '/templates/pageForm.latte');
    }
}