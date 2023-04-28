<?php
namespace App\AdminModule\Components\BlogCategory;

use App\Model\Factory\GridFactory;
use Nette\Application\UI\Control;
use App\Model\BlogCategory\BlogCategoryRepository;

class BlogCategoryGrid extends Control {
    
    private $gridFactory;

    public $onEdit = [];
    
    private $blogCategoryRepository;
    
     public function __construct(GridFactory $gridFactory, BlogCategoryRepository $blogCategoryRepository)
    {
        $this->gridFactory = $gridFactory;
        $this->blogCategoryRepository = $blogCategoryRepository;
    }
    
    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('blog_category.id');
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText(':blog_category_lang.name', 'Název');
        $grid->addColumnText(':blog_category_lang.slug', 'Slug');
         $grid->addAction('edit','', 'Edit', ['id'])
            ->setIcon('edit');
        return $grid;
    }
      public function handleEdit($id)
    {
        $this->onEdit($id);
    }
    
    public function render()
    {
        $this->template->render(__DIR__.'/templates/blogCategoryGrid.latte');
    }  
    
     public function getDataSource()
    {
        return $this->blogCategoryRepository->getAll();
    }
}
