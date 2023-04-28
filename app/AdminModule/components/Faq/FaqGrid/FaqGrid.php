<?php


namespace App\AdminModule\Components\Faq;


use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\Faq\FaqRepository;
use App\Model\LocaleRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class FaqGrid extends Control
{
    /**
     * @var FaqRepository
     */
    private FaqRepository $faqRepository;
    /**
     * @var GridFactory
     */
    private GridFactory $gridFactory;

    private $langId = 1;

    public $onEdit = [], $onLocaleChange = [];
    /**
     * @var FormFactory
     */
    private FormFactory $formFactory;
    /**
     * @var LocaleRepository
     */
    private LocaleRepository $localeRepository;

    public function __construct(FaqRepository $faqRepository, GridFactory $gridFactory, FormFactory $formFactory, LocaleRepository $localeRepository)
    {
        $this->faqRepository = $faqRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('question', 'Otázka');
        $grid->addColumnText('answer', 'Odpověď');
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

    public function showActive($data)
    {
        $out = new Html();
        if($data->active) {
            return $out->addHtml('<span class="text-success"><i class="fas fa-check"></i></span>');
        }
        return $out->addHtml('<span class="text-danger"><i class="fas fa-times"></i></span>');
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addSelect('lang_id', 'form.language', $this->localeRepository->getLangsToSelect())->setDefaultValue($this->langId);
        $form->addSubmit('submit', 'form.change');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function setLocaleId($id)
    {
        $this->langId = $id;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->langId = $values->lang_id;
        $this->onLocaleChange($this->langId);
        $this['grid']->redrawControl();
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function handleRemove($id)
    {
        $this->faqRepository->remove($id);
    }

    private function getDataSource()
    {
        return $this->faqRepository->getAll($this->langId);
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/faqGrid.latte');
    }
}