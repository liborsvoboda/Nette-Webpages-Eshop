<?php


namespace App\Model\Email;


use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\TplSettingsService;
use App\Model\Setting\SettingRepository;
use http\Exception\BadMethodCallException;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplateFactory;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Utils\Strings;
use Tracy\Debugger;

class EmailService
{
    private $linkGenerator,
        $templateFactory,
        $mailer,
        $appSettingsService,
        $tplSettingService,
        $settingRepository,
        $fromEmail,
        $fromName;

    public function __construct(
        $fromEmail, $fromName,
        LinkGenerator $linkGenerator,
        ITemplateFactory $templateFactory,
        IMailer $mailer,
        AppSettingsService $appSettingsService,
        TplSettingsService $tplSettingService,
        SettingRepository $settingRepository
    )
    {
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->linkGenerator = $linkGenerator;
        $this->templateFactory = $templateFactory;
        $this->mailer = $mailer;
        $this->appSettingsService = $appSettingsService;
        $this->tplSettingService = $tplSettingService;
        $this->settingRepository = $settingRepository;
    }

    protected function createTemplate()
    {
        $template = $this->templateFactory->createTemplate();
        $template->tplSetting = $this->tplSettingService;
        $template->getLatte()->addProvider('uiControl', $this->linkGenerator);
        return $template;
    }

    public function sendOrderEmail($order, $items, $orderState, $invoiceNumber = null)
    {
        $template = $this->createTemplate();
        $template->setFile($this->appSettingsService->getEmailTemplatesDir() . '/order/newOrder.latte');
        $template->order = $order;
        $template->items = $items;
        $template->blankImage = ProductRepository::NULL_IMG;
        $template->orderState = $orderState;
        $attachment = null;
        if ($invoiceNumber) {
            $attachment = $this->appSettingsService->getWwwDir() . DIRECTORY_SEPARATOR . 'upload/invoices/' . Strings::webalize($invoiceNumber) . '.pdf';
        }
        $this->sendEmail($template, $order['email'], null, null, $attachment);
        $this->sendEmail($template, $this->fromEmail, $order['email']);
        // Send mail to company
        $companyMailList = $this->getCompanyMailList();
        if ($companyMailList) {
            $this->sendEmail($template, $companyMailList);
        }
    }

    public function sendLostPasswordEmail($email, $hash)
    {
        $template = $this->createTemplate();
        $template->setFile($this->appSettingsService->getEmailTemplatesDir() . '/account/lostPassword.latte');
        $template->hash = $hash;
        $this->sendEmail($template, $email);
    }

    public function sendOrderChangeStatusEmail($orderNumber, $email, $body)
    {
        $template = $this->createTemplate();
        $template->setFile($this->appSettingsService->getEmailTemplatesDir() . '/order/orderUpdate.latte');
        $template->orderNumber = $orderNumber;
        $template->email = $email;
        $template->body = $body;
        $this->sendEmail($template, $email);
    }

    public function sendContactEmail($values, $to)
    {
        $template = $this->createTemplate();
        $template->setFile($this->appSettingsService->getEmailTemplatesDir().'/contact.latte');
        $template->values = $values;
        $this->sendEmail($template, $to);
    }

    public function sendAvailabilityEmail($values, $to)
    {
        $template = $this->createTemplate();
        $template->setFile($this->appSettingsService->getEmailTemplatesDir().'/availability.latte');
        $template->values = $values;
        $this->sendEmail($template, $to);
    }

    protected function sendEmail($template, $to, $fromEmail = null, $fromName = null, $attachment = null)
    {
        $mail = new Message();
        $fromEmail = $fromEmail ? $fromEmail : $this->fromEmail;
        $fromName = $fromName ? $fromName : $this->fromName;
        $mail->setFrom($fromEmail, $fromName);
        if (is_array($to)) {
            foreach ($to as $receiver) {
                $mail->addTo($receiver);
            }
        } else {
            $mail->addTo($to);
        }
        try {
            $mail->setHtmlBody($template);
        } catch (\Exception $e) {
            Debugger::log('Email sending error: ' . $e);
        }

        if ($attachment) {
            $mail->addAttachment($attachment);
        }
        try {
            $this->mailer->send($mail);
        } catch (\Exception $e) {
            Debugger::log('Email sending error: ' . $e);
        }
    }


    /**
     * @return array
     */
    private function getCompanyMailList(): array
    {
        $mail = $this->settingRepository->getValue('companyOrderEmail');
        $mailArray = explode(',', $mail);
        if ($mailArray) {
            $mailArray = array_filter($mailArray);
            $mailArray = array_map('trim', $mailArray);
            return $mailArray;
        }
        return [];
    }
}
