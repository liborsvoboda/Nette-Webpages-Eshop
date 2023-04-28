<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;


use App\FrontModule\Components\Cart\ICartCountFactory;
use App\FrontModule\Components\Cart\ICartAddFormFactory;
use App\FrontModule\Components\Cart\ICartModalFactory;
use App\Model\BaseRepository;
use App\Model\Product\ProductRepository;
use App\Model\Cart\CartRepository;
use App\Model\Category\CategoryRepository;
use App\Model\LocaleRepository;
use App\Model\Menu\MenuRepository;
use App\Model\Page\PageRepository;
use App\Model\Producer\ProducerRepository;
use App\Model\Search\SearchRepository;
use App\Model\Services\ImageService;
use App\Model\Setting\SettingRepository;
use App\Model\Services\TplSettingsService;
use App\Model\User\UserRepository;
use Contributte\Translation\LocalesResolvers\Session as TranslationSession;
use Nette\Localization\ITranslator;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\Multiplier;

abstract class BasePresenter extends Presenter
{

    /**
     * @var TplSettingsService
     * @inject
     */
    public $tplSettingsService;

    /**
     * @var ITranslator
     * @inject
     */
    public $translator;

    /**
     * @var TranslationSession
     * @inject
     */
    public $translatorSessionResolver;

    /**
     * @var MenuRepository
     * @inject
     */
    public $menuRepository;

    /**
     * @var PageRepository
     * @inject
     */
    public $pageRepository;

    /**
     * @var CategoryRepository
     * @inject
     */
    public $categoryRepository;

    /**
     * @var SearchRepository
     * @inject
     */
    public $searchRepository;

    /**
     * @var CartRepository
     * @inject
     */
    public $cartRepository;

	/**
     * @var ProductRepository
     * @inject
     */
    public $productRepository;

    /**
     * @var ICartCountFactory
     * @inject
     */
    public $cartCount;

	/**
     * @var ICartAddFormFactory
     * @inject
     */
    public $cartAddForm;

	/**
     * @var ICartModalFactory
     * @inject
     */
    public $cartModal;

    /**
     * @var ProducerRepository
     * @inject
     */
    public $producerRepository;

    /**
     * @var ImageService
     * @inject
     */
    public $imageService;

    /**
     * @var LocaleRepository
     * @inject
     */
    public $localeRepository;

    /**
     * @var SettingRepository
     * @inject
     */
    public $settingRepository;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    public $searchResult = null, $quickResult = null, $langId, $allowCart = true;

    public function startup()
    {
        parent::startup();
       // $this->cartRepository->clearCart();
        $this->langId = BaseRepository::getLang();
        $this->allowCart = ($this->langId != 3);
/*
        if (
            !$this->user->isLoggedIn() &&
            !$this instanceof SignPresenter &&
            !$this instanceof PagePresenter &&
            !$this instanceof AccountPresenter &&
            !$this instanceof WelcomePresenter
        ) {
            $this->redirect('Welcome:');
        }
*/
        if($this->getParameter('ref_no')) {
            $section = $this->session->getSection('referral');
            $section->ref_no = $this->getParameter('ref_no');
            //$this->redirect('Sign:up');
        }
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $locale = $this->localeRepository->getLocaleByLangId($this->langId);
        $this->template->freeDelivery = $this->settingRepository->getFreeDelivery($locale);
        $this->template->locales = $this->localeRepository->getLangsForHomepage();
        $this->template->langId = $this->langId;
        $this->template->allowCart = $this->allowCart;
        $this->template->tplSetting = $this->tplSettingsService;

        $this->template->addFunction('imgSize', function (...$args) {
            return $this->imageService->getImage($args);
        });

        $this->template->addFilter('echoSvg', function ($svg) {
            return file_get_contents(__DIR__ . '/../www/dist/svg/' . $svg . '.svg');
        });
        $changed = $this->cartRepository->recalculateCartWhenSignIn();
        if($changed === true) {
//            $this->redirect('this');
        }
        if($this->isAjax()) {
            $this->redrawControl('cartCount');
            $this->template->menuItems = $this->getMenuItems();
        } else {
            $this->template->menuItems = $this->getMenuItems();
            $this->template->footerColumn2 = $this->menuRepository->getFooterColumn2();
            $this->template->footerColumn3 = $this->menuRepository->getFooterColumn3();
        }

        $locale = $this->getParameter('locale');
        $this->template->searchResult = $this->searchResult;
        $this->template->quickResult = $this->quickResult;
        $this->template->producers = $this->producerRepository->getRandom();
        $this->template->mainMetaDescription = $this->pageRepository->getMainMetDescription($locale);
        $this->template->cartItems = count(array_filter((array)$this->cartRepository->getItems()));

        if (!isset($_COOKIE['floatText']) || $_COOKIE['floatText'] != 1) {
            $var = 'floatText';
            if ($locale !== 'sk') $var .= '_' . $locale;
            $this->template->floatText = $this->settingRepository->getValue($var);
        }
        $username = '';
        if($this->user->isLoggedIn()) {
            $userData = $this->userRepository->getById($this->user->id)->fetch();
            if($userData) {
                $username = ($userData->firstName || $userData->lastName) ? $userData->firstName. ' ' . $userData->lastName :explode('@',$userData->email)[0];
            }
        }
        $this->template->username = $username;
    }

    private function getMenuItems()
    {
        $menuItems['mega'] = $this->categoryRepository->getMainMenu();
        $menuItems['menu'] = $this->menuRepository->getMainMenu(BaseRepository::getLang());
        return $menuItems;
    }

    public function handleChangeLocale(string $locale)
    {
        $this->translatorSessionResolver->setLocale($locale);
        $this->redirect('this');
    }

    public function handleMainSearch($string)
    {
        $menuItems['mega'] = [];
        $menuItems['menu'] = [];
        $this->template->menuItems = $menuItems;
        $this->template->footerColumn2 = $this->menuRepository->getFooterColumn2();
        $this->template->footerColumn3 = $this->menuRepository->getFooterColumn3();
        $this->searchResult = $this->searchRepository->search($string);
        $this->redrawControl('mainNav');
        $this->redrawControl('searchSuggestion');
    }

    public function handleQuickSearch($string)
    {
        $this->quickResult = $this->searchRepository->search($string);
        $this->redrawControl('header');
        $this->redrawControl('quickSuggestion');
    }

    public function createComponentCartCount()
    {
        return $this->cartCount->create();
    }

	public function createComponentAddToCart()
	{
		return new Multiplier(function($productId) {
			$product = $this->productRepository->getById($productId)->fetch();
			$form = $this->cartAddForm->create($product);
			$form->onDone[] = function ($amount, $product) use ($productId) {
				if ($this->isAjax()) {
					$this->payload->addedToCart = true;
					$this->payload->addedToCartItem = [
						'id' => $productId,
						'amount' => $amount,
						'product' => $product
					];
					$this['cartModal']->setItem($productId, $amount);
					$this->redrawControl('cartModal');
					$this->redrawControl('cartCount');
				} else {
					$this->redirect('this');
				}
			};
			return $form;
		});
	}

	public function createComponentCartModal()
    {
        return $this->cartModal->create();
    }

}