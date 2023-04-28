<?php


namespace App\AdminModule\Components\EmailStatus;


use App\Model\Factory\FormFactory;
use App\Model\LocaleRepository;
use App\Model\Order\OrderRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class EmailStatusForm extends Control
{

    private $orderRepository, $formFactory;

    public $onDone = [];
    private LocaleRepository $localeRepository;

    public function __construct(OrderRepository $orderRepository, FormFactory $formFactory, LocaleRepository $localeRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
    }

    public function createComponentForm()
    {
        $statuses = $this->orderRepository->getOrderStatusesForForm();
        $form = $this->formFactory->create();
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $form->addContainer('locale' . $locale->id);
            $form['locale' . $locale->id]->addTextArea('email2l'.$locale->id, 'Stav: Vyřizuje se text')
                ->setHtmlAttribute('class', 'editor mb-4')
                ->setDefaultValue($statuses[$locale->id][OrderRepository::STATUS_PROCESSING]['email']);
            $form['locale' . $locale->id]->addText('name2l'.$locale->id, 'Stav: Vyřizuje se předmět')
                ->setHtmlAttribute('class', 'editor mb-4')
                ->setDefaultValue($statuses[$locale->id][OrderRepository::STATUS_PROCESSING]['subject'] ?? '');
            $form['locale' . $locale->id]->addTextArea('email4l'.$locale->id, 'Stav: Odeslaná text')
                ->setHtmlAttribute('class', 'editor')
                ->setDefaultValue($statuses[$locale->id][OrderRepository::STATUS_SENT]['email']);
            $form['locale' . $locale->id]->addText('name4l'.$locale->id, 'Stav: Odeslaná předmět')
                ->setHtmlAttribute('class', 'editor')
                ->setDefaultValue($statuses[$locale->id][OrderRepository::STATUS_SENT]['subject']);
            $form['locale' . $locale->id]->addTextArea('email6l'.$locale->id, 'Stav: Stornovaná text')
                ->setHtmlAttribute('class', 'editor')
                ->setDefaultValue($statuses[$locale->id][OrderRepository::STATUS_STORNO]['email']);
            $form['locale' . $locale->id]->addText('name6l'.$locale->id, 'Stav: Stornovaná předmět')
                ->setHtmlAttribute('class', 'editor')
                ->setDefaultValue($statuses[$locale->id][OrderRepository::STATUS_STORNO]['subject']);
        }
        $form->addSubmit('submit', 'Uložit');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        bdump($form);
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();

        $this->orderRepository->updateOrderStatusEmail($values);
        $this->onDone();
    }

    public function render()
    {
        $this->template->locales = $this->localeRepository->getAll();
        $this->template->render(__DIR__.'/templates/emailStatusForm.latte');
    }
}