<?php

namespace App\AdminModule\Components\Blog;

use App\Model\Blog\BlogRepository;
use App\Model\Factory\GridFactory;
use Nette\Application\UI\Control;
use Nette\Utils\Html;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class BlogGrid extends Control
{

    public $onEdit = [];

    private $blogRepository;

    private $gridFactory;

    public function __construct(BlogRepository $blogRepository, GridFactory $gridFactory)
    {
        $this->blogRepository = $blogRepository;
        $this->gridFactory = $gridFactory;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('blog.id');
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText(':blog_lang.title', 'Název');
        $grid->addColumnText(':blog_lang.slug', 'Slug');
        $grid->addColumnText('active', 'Viditelné')
            ->setAlign('center')
            ->setRenderer([$this, 'showActive']);
        $grid->addAction('edit','', 'Edit', ['id'])
            ->setIcon('edit');
        $grid->addAction('remove', '', 'remove', ['id'])
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        return $grid;
    }

    public function handleRemove($id)
    {
        $this->blogRepository->remove($id);
        $this['grid']->redraWControl();
    }


    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function getDataSource()
    {
        $data = $this->blogRepository->getAllAdmin();
        bdump($data->fetchAll());
        return $data;
    }

    public function showActive($data)
    {
        $out = new Html();
        if($data->active) {
            return $out->addHtml('<span class="text-success"><i class="fas fa-check"></i></span>');
        }
        return $out->addHtml('<span class="text-danger"><i class="fas fa-times"></i></span>');
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/blogGrid.latte');
    }
}