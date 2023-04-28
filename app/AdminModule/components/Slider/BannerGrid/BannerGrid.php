<?php

namespace App\AdminModule\Components\Slider;

use App\Model\Category\CategoryRepository;
use App\Model\Factory\GridFactory;
use App\Model\Slider\SliderRepository;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class BannerGrid extends Control
{
    private $sliderRepository, $categoryRepository, $gridFactory;

    public $onEdit = [];

    public function __construct(SliderRepository $sliderRepository, CategoryRepository $categoryRepository, GridFactory $gridFactory)
    {
        $this->sliderRepository = $sliderRepository;
        $this->categoryRepository = $categoryRepository;
        $this->gridFactory = $gridFactory;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('image', 'Obrázek')
            ->setAlign('center')
            ->setTemplate(__DIR__.'/templates/image.latte');

        $grid->addColumnText('category.name', 'Kategorie')
            ->setRenderer(function ($row){
                $name = $this->categoryRepository->getById($row->category_id);
                return $name->name;
            });
        $grid->addAction('edit','', 'Edit', ['id'])
            ->setIcon('edit');
        $grid->addAction('remove', '')
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        return $grid;
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function handleRemove($id)
    {
        $this->sliderRepository->removeBanner($id);
        $this['grid']->redraWControl();
    }


    private function getDataSource()
    {
        return $this->sliderRepository->getAllBanners();
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/bannerGrid.latte');
    }

}