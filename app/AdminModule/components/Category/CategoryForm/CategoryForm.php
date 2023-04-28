<?php


namespace App\AdminModule\Components\Category;


use App\Model\Category\CategoryRepository;
use App\Model\Factory\FormFactory;
use App\Model\Feed\FeedRepository;
use App\Model\LocaleRepository;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class CategoryForm extends Control
{
    private $categoryRepository, $feedRepository, $userLevelRepository, $formFactory, $localeRepository;

    public $categoryId = null,
        $onDone = [];

    public function __construct(CategoryRepository $categoryRepository,
                                FormFactory $formFactory,
                                FeedRepository $feedRepository,
                                UserLevelRepository $userLevelRepository,
                                LocaleRepository $localeRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->formFactory = $formFactory;
        $this->feedRepository = $feedRepository;
        $this->userLevelRepository = $userLevelRepository;
        $this->localeRepository = $localeRepository;
    }

    public function setEdit($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    public function createComponentForm()
    {
        if ($this->categoryId) {
            $category = $this->categoryRepository->getById($this->categoryId);
        }
        $form = $this->formFactory->create();
        $form->addCheckbox('visible', 'Viditelná')->setDefaultValue(true);
        $form->addUpload('image', 'Obrázek');
        $form->addSelect('parent_id', 'Nadřazená kategorie', $this->categoryRepository->getFullPathToSelect())->setHtmlAttribute('class', 'select2')->setPrompt('Žádná nadřazená kategorie');
        $form->addSelect('heureka_id', 'Heureka kategorie', $this->feedRepository->getHeurekaCatsToSelect())->setPrompt('Heureka kategorie')->setHtmlAttribute('class', 'select2');
        $form->addSelect('gtaxonomy_id', 'form.gtaxonomy', $this->feedRepository->getGoogleCatsToSelect())->setPrompt('form.gtaxonomy')->setHtmlAttribute('class', 'select2');
        $form->addSelect('pricemania_id', 'Pricemania kategorie', $this->feedRepository->getPricemaniaCatsToSelect())->setPrompt('Pricemania kategorie')->setHtmlAttribute('class', 'select2');
        $userLevels = $this->userLevelRepository->getAll();
        foreach ($userLevels as $userLevel) {
            $form->addText('usrlvl' . $userLevel->id, 'Sleva pro: ' . $userLevel->name);
        }
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $form->addContainer('locale' . $locale->id);
            $form['locale' . $locale->id]->addText('name', 'Název')->setRequired('Zadejte název');
            $form['locale' . $locale->id]->addTextArea('description', 'Popis')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addTextArea('description_end', 'Popis na konci')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addText('slug', 'Slug');
            $form['locale' . $locale->id]->addText('seoTitle', 'SEO Title');
            $form['locale' . $locale->id]->addTextArea('seoDescription', 'SEO description');
        }
        if ($this->categoryId) {
            $form['parent_id']->setDefaultValue($category->parent_id);
            $form['visible']->setDefaultValue($category->visible);
            //$form['heureka_id']->setDefaultValue($category->heureka_id);
            $form['pricemania_id']->setDefaultValue($category->pricemania_id);
            $form['gtaxonomy_id']->setDefaultValue($category->gtaxonomy_id);
            $discounts = json_decode($category->discounts, true);
            foreach ($userLevels as $userLevel) {
                if (isset($discounts[$userLevel->id])) {
                    $form['usrlvl' . $userLevel->id]->setDefaultValue($discounts[$userLevel->id]);
                }
            }
            foreach ($locales as $locale) {
                $langItems = $this->categoryRepository->getLangItems($this->categoryId, $locale->lang->id)->fetch();
                @$form['locale' . $locale->id]['name']->setDefaultValue($langItems->name);
                @$form['locale' . $locale->id]['description']->setDefaultValue($langItems->description);
                @$form['locale' . $locale->id]['description_end']->setDefaultValue($langItems->description_end);
                @$form['locale' . $locale->id]['seoDescription']->setDefaultValue($langItems->seoDescription);
                @$form['locale' . $locale->id]['seoTitle']->setDefaultValue($langItems->seoTitle);
                @$form['locale' . $locale->id]['slug']->setDefaultValue($langItems->slug)->setHtmlAttribute('readonly', 'readonly');
            }
        }
        $form->addSubmit('submit', 'Uložit');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->categoryRepository->save($values, $this->categoryId);
        $this->onDone($this->categoryId);
    }

    public function render()
    {
        if ($this->categoryId) {
            $category = $this->categoryRepository->getById($this->categoryId);
            $this->template->image = $category->image;
        }
        $this->template->userLevels = $this->userLevelRepository->getAll();
        $this->template->locales = $this->localeRepository->getAll();
        $this->template->render(__DIR__ . '/templates/categoryForm.latte');
    }
}