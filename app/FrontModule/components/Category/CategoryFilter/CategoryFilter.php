<?php


namespace App\FrontModule\Components\Category;


use App\Model\Category\CategoryRepository;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Http\Session;

class CategoryFilter extends Control
{
    protected $categoryRepository, $appSettingsService, $session, $section, $checked, $parent;
    public $onDone = [];

    public function __construct($section, CategoryRepository $categoryRepository, AppSettingsService $appSettingsService, Session $session, $parent = null)
    {
        $this->categoryRepository = $categoryRepository;
        $this->appSettingsService = $appSettingsService;
        $this->parent = $parent;
        $this->session = $session;
        $this->section = $session->getSection($section);
        $this->section->setExpiration('2 minutes');
    }

    public function getFiltered()
    {
        return isset($this->section->filter) ? $this->section->filter : null;
    }

    private function makeDefaults()
    {
        $def = '';
        foreach (explode(',', $this->getFiltered()) as $item) {
            $def .= 'cat-'.$item.',';
        }
        $def = rtrim($def, ",");
        return explode(',',$def);
    }

    private function setFilterSession($values)
    {
        $this->section->filter = $values;
    }

    private function getList()
    {
        return $this->categoryRepository->getAllToFilter($this->parent);
    }

    public function createComponentForm()
    {
        $form = new Form();
        $form->addCheckboxList('type', '', $this->getList());
        if($this->getFiltered()) {
            $form->setDefaults(['type' => $this->makeDefaults()]);
        }
        $form->addSubmit('filter');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $reset = false;
        $filt = null;
        foreach ($values['type'] as $value) {
            $filt .= str_replace('cat-', '', $value).',';
        }
        $filt = substr($filt, 0, -1);
        if($reset) {
            $filt = null;
        }
        $this->setFilterSession($filt);
        $this->onDone($this->getFiltered());
    }

    public function render()
    {
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/Category/CategoryFilter/categoryFilter.latte');
    }

}