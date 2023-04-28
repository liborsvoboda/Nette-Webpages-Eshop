<?php
declare(strict_types=1);

namespace App\AdminModule\Components\Menu;


use App\Model\Factory\FormFactory;
use App\Model\LocaleRepository;
use App\Model\Menu\MenuRepository;
use App\Model\Search\SearchRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class MenuForm extends Control
{
    use SmartObject;

    /**
     * @var MenuRepository
     */
    private $menuRepository, $formFactory, $localeRepository, $menuId = null;

    public $onDone = [];

    /**
     * @var SearchRepository
     */
    private $searchRepository;

    public function __construct(MenuRepository $menuRepository,
                                SearchRepository $searchRepository,
                                FormFactory $formFactory,
                                LocaleRepository $localeRepository)
    {
        $this->menuRepository = $menuRepository;
        $this->searchRepository = $searchRepository;
        $this->localeRepository = $localeRepository;
        $this->formFactory = $formFactory;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addSelect('position', 'Umístění', MenuRepository::POSITION)->setRequired();
        $form->addInteger('sort', 'Poradie')->setDefaultValue(1)->setRequired();
        $form->addSelect('parent_id', 'Nadradená položka', $this->menuRepository->getMenuItems())->setPrompt('Žiadna položka');
        $form->addSubmit('submit', 'Uložit');
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $form->addContainer('locale'.$locale->id);
            $form['locale'.$locale->id]->addText('title', 'form.title')->setRequired();
            $form['locale'.$locale->id]->addText('slug', 'form.slug')->setRequired();
        }
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        $form->onSuccess[] = [$this, 'formSuccess'];
        if($this->menuId) {
            $defaults = $this->menuRepository->getById($this->menuId)->fetch();
            $form['position']->setDefaultValue($defaults->position);
            $form['parent_id']->setDefaultValue($defaults->parent_id);
            $form['sort']->setDefaultValue($defaults->sort);
            foreach ($locales as $locale) {
                $langItems = $this->menuRepository->getLangItems($this->menuId, $locale->lang->id)->fetch();
                @$form['locale'.$locale->id]['title']->setDefaultValue($langItems->title);
                @$form['locale'.$locale->id]['slug']->setDefaultValue($langItems->slug);//->setHtmlAttribute('readonly', 'readonly');
            }
        }
        return $form;
    }

    public function setEdit($menuId)
    {
        $this->menuId = $menuId;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        if($this->menuId) {
            $this->menuRepository->update($this->menuId, $values);
        } else {
            $this->menuRepository->add($values);
        }
        $this->onDone();
    }

    public function render()
    {
        $this->template->locales = $this->localeRepository->getAll();
        $this->template->render(__DIR__ . '/templates/menuForm.latte');
    }


    /**
     * @param $string
     */
    public function handleQuickSearch(string $string = null)
    {
        $search = $this->searchRepository->search($string);
        $this->template->searchResult = $search;
        $this->redrawControl('categoryFormSearchResult');
    }
}
