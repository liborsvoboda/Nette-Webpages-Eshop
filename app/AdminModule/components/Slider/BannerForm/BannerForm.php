<?php


namespace App\AdminModule\Components\Slider;


use App\Model\Category\CategoryRepository;
use App\Model\Factory\FormFactory;
use App\Model\Slider\SliderRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class BannerForm extends Control
{
    private $sliderRepository, $bannerId, $banner, $categoryRepository, $formFactory;

    public $onDone = [];

    public function __construct(SliderRepository $sliderRepository, CategoryRepository $categoryRepository, FormFactory $formFactory)
    {
        $this->sliderRepository = $sliderRepository;
        $this->categoryRepository = $categoryRepository;
        $this->formFactory = $formFactory;
    }

    public function setEdit($sliderId)
    {
        $this->bannerId = $sliderId;
        $this->banner = $this->sliderRepository->getBannerById($this->bannerId)->fetch();
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addSelect('category_id', 'Kategorie', $this->categoryRepository->getFullPathToSelect())
            ->setHtmlAttribute('class', 'select2')
            ->setPrompt('Kategorie')
            ->setRequired('Vyberte kategorii');
        $form->addUpload('image', 'Obrázek');
        $form->addSubmit('submit', 'Uložit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        if ($this->bannerId) {
            $form->setDefaults($this->banner);
        }
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        if($this->bannerId) {
            $this->sliderRepository->updateBanner($values, $this->bannerId);
        } else {
            $this->sliderRepository->addBanner($values);
        }
        $this->onDone();
    }

    public function render()
    {
        if ($this->bannerId) {
            $this->template->image = $this->banner->image;
        }
        $this->template->setFile(__DIR__ . '/templates/bannerForm.latte');
        $this->template->render();
    }
}