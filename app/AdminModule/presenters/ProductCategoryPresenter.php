<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Category\ICategoryFormFactory;
use App\AdminModule\Components\Category\IProductCategoryGridFactory;
use App\Model\BaseRepository;
use App\Model\Category\CategoryRepository;
use Nette\Schema\Elements\Base;

class ProductCategoryPresenter extends CataloguePresenter
{
    /**
     * @var IProductCategoryGridFactory
     * @inject
     */
    public $categoryGrid;

    /**
     * @var CategoryRepository
     * @inject
     */
    public $categoryRepository;

    /**
     * @var ICategoryFormFactory
     * @inject
     */
    public $categoryForm;

    private $categoryId;

    /**
     * @persistent
     */
    public $lang = 1;

    public function startup()
    {
        parent::startup();
        BaseRepository::setLang($this->lang);
    }

    public function createComponentProductCategoryGrid()
    {
        $grid = $this->categoryGrid->create();
        $grid->setLangId($this->lang);
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        $grid->onLangChange[] = function ($langId) {
            $this->lang = $langId;
            $this->redirect('this');
        };
        return $grid;
    }

    public function actionTest()
    {

    }

    public function actionEdit($id)
    {
        $this->categoryId = $id;
    }

    public function createComponentCategoryForm()
    {
        $form = $this->categoryForm->create();
        $form->setEdit($this->categoryId);
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }
}