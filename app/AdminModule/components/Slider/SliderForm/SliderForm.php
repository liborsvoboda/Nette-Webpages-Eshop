<?php


namespace App\AdminModule\Components\Slider;


use App\Model\Factory\FormFactory;
use App\Model\Slider\SliderRepository;
use App\Model\LocaleRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class SliderForm extends Control
{
    private $sliderRepository, $localeRepository, $sliderId, $slider, $formFactory;

    public $onDone = [];

    public function __construct(SliderRepository $sliderRepository, FormFactory $formFactory, LocaleRepository $localeRepository)
    {
        $this->sliderRepository = $sliderRepository;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
    }

    public function setEdit($sliderId)
    {
        $this->sliderId = $sliderId;
        $this->slider = $this->sliderRepository->getById($this->sliderId)->fetch();
    }

    public function createComponentForm()
    {
        $langs = $this->localeRepository->getLangsToSelect();

        $form = $this->formFactory->create();
        $form->addCheckbox('active', 'Aktivní')->setDefaultValue(true);
        $form->addText('ord', 'Pořadí')->setDefaultValue(1);
        $form->addText('heading', 'Nadpis');
        $form->addText('text', 'Text');
        $form->addText('button', 'Text na button');
        $form->addText('url', 'Odkaz');
        $form->addUpload('image', 'Obrázek');
        $form->addUpload('background', 'Pozadí');
        $form->addSelect('lang_id', 'Jazyk', $langs);
        $form->addSubmit('submit', 'Uložit');
        $form->onSuccess[] = [$this, 'formSuccess'];
        if ($this->sliderId) {
            $form->setDefaults($this->slider);
        }
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        if($this->sliderId) {
            $this->sliderRepository->update($values, $this->sliderId);
        } else {
            $this->sliderRepository->add($values);
        }
        $this->onDone();
    }

    public function render()
    {
        if ($this->sliderId) {
            $this->template->image = $this->slider->image;
        }
        $this->template->setFile(__DIR__ . '/templates/sliderForm.latte');
        $this->template->render();
    }
}