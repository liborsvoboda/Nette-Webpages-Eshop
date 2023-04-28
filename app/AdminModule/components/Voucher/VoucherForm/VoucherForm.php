<?php


namespace App\AdminModule\Components\Voucher;


use App\Model\LocaleRepository;
use App\Model\Voucher\VoucherRepository;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;
use Tracy\Debugger;

class VoucherForm extends Control
{
    private $voucherRepository, $voucher, $localeRepository, $userLevelRepository;
    public $onDone = [];
    public $voucherId = null;

    public function __construct(VoucherRepository $voucherRepository, LocaleRepository $localeRepository, UserLevelRepository $userLevelRepository)
    {
        $this->voucherRepository = $voucherRepository;
        $this->localeRepository = $localeRepository;
        $this->userLevelRepository = $userLevelRepository;
    }

    public function createComponentForm()
    {
        $form = new Form();
        $form->addText('code', 'Kód')->setRequired();
        $form->addInteger('value', 'Sleva')->setRequired();
        $form->addSelect('type', 'Typ slevy', VoucherRepository::VOCHER_TYPE);
        $form->addText('dateFrom', 'Platnost od')->setHtmlAttribute('type', 'date')->setRequired();
        $form->addText('dateTo', 'Platnost do')->setHtmlAttribute('type', 'date')->setRequired();
        $form->addText('priceFrom', 'Cena od')->setNullable();
        $form->addText('priceTo', 'Cena do')->setNullable();
        $form->addSelect('lang_id', 'Jazyk', $this->localeRepository->getLangsToSelect());
        $form->addText('parent_ref_no', 'Referenční číslo')->setNullable();
        $form->addSelect('max_user_group_id', 'Maximální Level', $this->userLevelRepository->getForSelect());
        $form->addSubmit('submit', 'Uložit');
        $form->setRenderer(new Bs4FormRenderer(FormLayout::HORIZONTAL));

        if ($this->voucherId) {
            $form->setDefaults($this->voucher);
            $dateFrom = new DateTime($this->voucher->dateFrom);
            $dateTo = new DateTime($this->voucher->dateTo);
            $form['dateFrom']->setDefaultValue($dateFrom->format('Y-m-d'));
            $form['dateTo']->setDefaultValue($dateTo->format('Y-m-d'));
        }

        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        if ($this->voucherId) {
            $this->voucherRepository->update($this->voucherId, $values);
        } else {
            $this->voucherRepository->add($values);
        }
        $this->onDone();
    }

    public function setEdit($voucherId)
    {
        $this->voucherId = $voucherId;
        $this->voucher = $this->voucherRepository->getById($this->voucherId)->fetch();
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/voucherForm.latte');
    }
}
