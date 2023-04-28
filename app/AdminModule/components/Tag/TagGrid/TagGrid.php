<?php

namespace App\AdminModule\Components\Tag;

use App\Model\Factory\GridFactory;
use Nette\Application\UI\Control;
use App\Model\Tag\TagRepository;

class TagGrid extends Control {


    public $onEdit = [];


   private $tagRepository, $gridFactory;

    public function __construct(TagRepository $tagRepository, GridFactory $gridFactory)
    {
         $this->gridFactory = $gridFactory;
         $this->tagRepository = $tagRepository;

    }

    public function handleEdit($id) {
        $this->onEdit($id);
    }


    public function createComponentGrid() {
        $grid = $this->gridFactory->create();
        $grid->setPrimaryKey('tag.id');
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText(':tag_lang.title', 'Název');

        $grid->addColumnText(':tag_lang.slug', 'Slug');
        $grid->addAction('edit', '', 'Edit', ['id'])
                ->setIcon('edit');

        return $grid;
    }

    public function getDataSource() {
        return $this->tagRepository->getAll();
    }

    public function render() {
        $this->template->render(__DIR__ . '/templates/tagGrid.latte');
    }

}
