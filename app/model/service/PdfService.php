<?php


namespace App\Model\Services;


use App\Model\Order\OrderRepository;
use App\Model\Product\PriceFacade;
use App\Model\Setting\SettingRepository;
use App\Model\User\UserRepository;
use Joseki\Application\Responses\InvalidStateException;
use Joseki\Application\Responses\PdfResponse;
use Nette\Application\UI\ITemplateFactory;
use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\Localization\ITranslator;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;

class PdfService
{

    private $templateFactory, $appSettingsService, $settingRepository, $tplSettingService, $priceFacade, $translator, $userRepository, $db;

    private $symbols = [
        'sk' => '€',
        'cz' => 'Kč'
    ];

    public function __construct(
        ITemplateFactory $templateFactory,
        AppSettingsService $appSettingsService,
        SettingRepository $settingRepository,
        TplSettingsService $tplSettingService,
        ITranslator $translator,
        PriceFacade $priceFacade,
        UserRepository $userRepository,
        Context $db
    ) {
        $this->templateFactory = $templateFactory;
        $this->appSettingsService = $appSettingsService;
        $this->settingRepository = $settingRepository;
        $this->tplSettingService = $tplSettingService;
        $this->priceFacade = $priceFacade;
        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->db = $db;
    }

    public function makeInvoice($invoiceNumber, $orderData, $type, $invoiceData)
    {
        $template = $this->templateFactory->createTemplate();
        switch ($type) {
            case OrderRepository::INVOICE_PROFORMA :
                $file = 'proforma.latte';
                $title = 'Proforma-'.$invoiceNumber;
                break;
            case  OrderRepository::INVOICE_REGULAR :
                $file = 'invoice.latte';
                $title = 'Faktura-'.$invoiceNumber;
                break;
            case OrderRepository::INVOICE_STORNO :
                $file = 'storno.latte';
                $title = 'Dobropis-'.$invoiceNumber;
        }

        $template->tplSetting = $this->tplSettingService;
        $template->setFile($this->appSettingsService->getPdfTemplatesDir().'/'.$file);
        $template->invoiceData = $invoiceData;
        $template->orderData = $orderData;
        $template->orderItems = $orderData->related('order_item')->fetchAll();
        $template->invoiceNumber = $invoiceNumber;
        $template->priceFacade = $this->priceFacade;
        $template->settings = $this->settingRepository->getAll()->fetchPairs('key', 'value');
        $pdf = new PdfResponse($template);
        $pdf->documentTitle = $title;
        try {
            $pdf->save($this->appSettingsService->getWwwDir().DIRECTORY_SEPARATOR.'upload/invoices', Strings::webalize($invoiceNumber));
            $pdf->setSaveMode(PdfResponse::INLINE);
        } catch (InvalidStateException $e) {

        }

        return($pdf);
    }

    public function makeCommissionList($data, $month, $year, $locale = 'sk')
    {
        $template = $this->templateFactory->createTemplate();
        $template->tplSetting = $this->tplSettingService;
        $template->data = $data;
        $template->month = $month;
        $template->year = $year;
        $template->users = $this->userRepository->getAll()->fetchAll();
        $template->symbol = $this->symbols[$data['locale_id']];
        $this->translator->setLocale($locale);
        $template->setTranslator($this->translator);
        $template->run = $this->db->table('monthly_run')->where('month', $month)->where('year', $year)->fetchPairs('user_id', 'user_level_id');
        $template->levels = $this->db->table('user_level')->fetchPairs('id', 'name');
        $template->setFile($this->appSettingsService->getPdfTemplatesDir().'/monthlyStatement/test.latte');
        $pdf = new PdfResponse($template);
        $pdf->mpdfConfig = ['default_font' => 'ubuntu'];
        $pdf->documentTitle = 'Výpis';
        try {
            FileSystem::createDir($this->appSettingsService->getWwwDir().DIRECTORY_SEPARATOR.'upload/statements');
            $pdf->save($this->appSettingsService->getWwwDir().DIRECTORY_SEPARATOR.'upload/statements', Strings::webalize($data['user']['id'].'-'.$year.'-'.$month));
            $pdf->setSaveMode(PdfResponse::INLINE);
        } catch (InvalidStateException $e) {

        }
        return($pdf);
    }
}