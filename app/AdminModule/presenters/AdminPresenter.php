<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Admin\IDatePickFormFactory;
use App\Model\Services\UserManager;
use App\Model\Services\TplSettingsService;
use Contributte\Translation\LocalesResolvers\Session;
use Nette\Application\UI\Presenter;
use Nette\Localization\ITranslator;

abstract class AdminPresenter extends Presenter
{

    /**
     * @var TplSettingsService
     * @inject
     */
    public $tplSettingsService;

    /**
     * @persistent
     */
    public $loc;

    /** @var ITranslator
     * @inject
     */
    public $translator;

    /** @var Session
     * @inject
     */
    public $translatorSessionResolver;

    /**
     * @var IDatePickFormFactory
     * @inject
     */
    public $datePickForm;

    /**
     * @persistent
     */
    public $from = null;

    /**
     * @persistent
     */
    public $to = null;

    public $sideMenu = [
        [
            'action' => 'Order:default',
            'presenter' => 'Order:*',
            'label' => 'Objednávky'
        ],
        [
            'action' => 'Customer:default',
            'presenter' => 'Customer:*',
            'label' => 'Zákazníci'
        ]
    ];

    public $menuItems = [
        [
            'action' => 'Homepage:default',
            'presenter' => 'Homepage:*',
            'label' => 'Prehľad'
        ],
        [
            'action' => 'Customer:default',
            'presenter' => 'Customer:*',
            'label' => 'Zákazníci a partneri'
        ],
        [
            'action' => 'Order:default',
            'presenter' => 'Order:default',
            'label' => 'Objednávky'
        ],
        [
            'action' => 'Order:customer',
            'presenter' => 'Order:customer',
            'label' => 'Zák.Objednávky'
        ],
        [
            'action' => 'Commission:default',
            'presenter' => 'Commission:*',
            'label' => 'Provízie'
        ],
        [
            'action' => 'MonthlyCommission:default',
            'presenter' => 'MonthlyCommission:*',
            'label' => 'Mesačné provízie'
        ],
        [
            'action' => 'Product:default',
            'presenter' => 'Product:*',
            'label' => 'Produkty'
        ],
        [
            'action' => 'ProductReview:default',
            'presenter' => 'ProductReview:*',
            'label' => 'Recenzie'
        ],
        [
            'action' => 'ProductCategory:default',
            'presenter' => 'ProductCategory:*',
            'label' => 'Kategorie'
        ],
        [
            'action' => 'Shipping:default',
            'presenter' => 'Shipping:*',
            'label' => 'Spôsoby doručenia'
        ],
        [
            'action' => 'Payment:default',
            'presenter' => 'Payment:*',
            'label' => 'Spôsoby platby'
        ],
        [
            'action' => 'UserLevel:default',
            'presenter' => 'UserLevel:*',
            'label' => 'Pozície'
        ],
        [
            'action' => 'Page:default',
            'presenter' => 'Page:*',
            'label' => 'Stránky'
        ],
        [
            'action' => 'Blog:default',
            'presenter' => 'Blog:*',
            'label' => 'Články'
        ],
        [
            'action' => 'Menu:default',
            'presenter' => 'Menu:*',
            'label' => 'Menu'
        ],
        [
            'action' => 'Faq:default',
            'presenter' => 'Faq:*',
            'label' => 'Poradna'
        ],
        [
            'action' => 'Slider:default',
            'presenter' => 'Slider:*',
            'label' => 'Slider'
        ],
        [
            'action' => 'ProductAttribute:default',
            'presenter' => 'ProductAttribute:*',
            'label' => 'Atributy'
        ],
        [
            'action' => 'MarketingGallery:default',
            'presenter' => 'MarketingGallery:*',
            'label' => 'Marketingová galeria'
        ],
        [
            'action' => 'EmailStatus:default',
            'presenter' => 'EmailStatus:*',
            'label' => 'Emailové stavy'
        ],
        [
            'action' => 'Voucher:default',
            'presenter' => 'Voucher:*',
            'label' => 'Slevové kupóny'
        ],
    ];

    public function startup()
    {
        parent::startup();
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect(':Front:Sign:in');
        }
        if($this->getUser()->getRoles()[0] != UserManager::USER_ADMIN) {
            $this->redirect('Sign:in');
        }
     }

    public function beforeRender()
    {
        $this->template->tplSetting = $this->tplSettingsService;
//        $this->translatorSessionResolver->setLocale($this->locale);
        $this->template->locale = $this->getParameter('locale') ?: $this->tplSettingsService->getDefaultLocale();
        $this->template->sideMenu = $this->sideMenu;
        $this->template->menuItems = $this->menuItems;
        $this->from = $this->from ?? $this['datePickForm']->getFrom();
        $this->to = $this->to ?? $this['datePickForm']->getTo();
    }

    public function createComponentDatePickForm()
    {
        $form = $this->datePickForm->create();
        $form->setFrom($this->from);
        $form->setTo($this->to);
        $form->onDone[] = function ($from, $to) {
            $this->from = $from;
            $this->to = $to;
            $this->redirect('this');
        };
        return $form;
    }
}