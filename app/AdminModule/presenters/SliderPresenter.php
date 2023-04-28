<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Slider\IBannerFormFactory;
use App\AdminModule\Components\Slider\IBannerGridFactory;
use App\AdminModule\Components\Slider\ISliderFormFactory;
use App\AdminModule\Components\Slider\ISliderGridFactory;

class SliderPresenter extends ContentPresenter
{
    /**
     * @var ISliderGridFactory
     * @inject
     */
    public $sliderGrid;

    /**
     * @var ISliderFormFactory
     * @inject
     */
    public $sliderForm;

    /**
     * @var IBannerGridFactory
     * @inject
     */
    public $bannerGrid;

    /**
     * @var IBannerFormFactory
     * @inject
     */
    public $bannerForm;

    private $sliderId = null, $bannerId = null;

    public function actionEdit($id)
    {
        $this->sliderId = $id;
    }

    public function actionBannerEdit($id)
    {
        $this->bannerId = $id;
    }

    public function createComponentSliderGrid()
    {
        $grid = $this->sliderGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('edit', $id);
        };
        return $grid;
    }

    public function createComponentSliderForm()
    {
        $form = $this->sliderForm->create();
        if($this->sliderId) {
            $form->setEdit($this->sliderId);
        }
        $form->onDone[] = function () {
            $this->redirect('default');
        };
        return $form;
    }

    public function createComponentBannerGrid()
    {
        $grid = $this->bannerGrid->create();
        $grid->onEdit[] = function ($id) {
            $this->redirect('bannerEdit', $id);
        };
        return $grid;
    }

    public function createComponentBannerForm()
    {
        $form = $this->bannerForm->create();
        if($this->bannerId) {
            $form->setEdit($this->bannerId);
        }
        $form->onDone[] = function () {
            $this->redirect('banner');
        };
        return $form;
    }

}