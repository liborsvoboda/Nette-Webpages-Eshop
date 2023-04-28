<?php

declare(strict_types=1);

namespace App;

use Apitte\Presenter\ApiRoute;
use App\Components\Macros\PriceFilter;
use App\Model\BaseRepository;
use App\Model\Blog\BlogRepository;
use App\Model\Category\CategoryRepository;
use App\Model\LocaleRepository;
use App\Model\Page\PageRepository;
use App\Model\Producer\ProducerRepository;
use App\Model\Product\PriceFacade;
use App\Model\Product\ProductRepository;
use App\Model\Setting\SettingRepository;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Http\Request;
use Nette\Neon\Neon;
use Nette\Utils\Strings;
use Tracy\Debugger;

final class RouterFactory
{

    private $productRepository,
        $pageRepository,
        $categoryRepository,
        $producerRepository,
        $blogRepository,
        $localeRepository,
        $settingRepository,
        $priceFilter,
        $request;

    private $allowedLocales;

    private $defaultLocale = 'sk';

    public function __construct(ProductRepository $productRepository,
                                PageRepository $pageRepository,
                                CategoryRepository $categoryRepository,
                                ProducerRepository $producerRepository,
                                BlogRepository $blogRepository,
                                Request $request,
                                LocaleRepository $localeRepository,
                                SettingRepository $settingRepository,
                                PriceFilter $priceFilter
    )
    {
        $this->productRepository = $productRepository;
        $this->pageRepository = $pageRepository;
        $this->categoryRepository = $categoryRepository;
        $this->producerRepository = $producerRepository;
        $this->blogRepository = $blogRepository;
        $this->localeRepository = $localeRepository;
        $this->settingRepository = $settingRepository;
        $this->priceFilter = $priceFilter;
        $this->request = $request;
    }

    public function createRouter(): RouteList
    {
        $allowedLocales = $this->localeRepository->getAll();
        foreach ($allowedLocales as $allowedLocale) {
            $this->allowedLocales[] = $allowedLocale->lang->locale;
        }
        $this->defaultLocale = $this->settingRepository->getDefaultLocale();
        $locale = $this->getLocale();
        $routes = $this->readFile($locale);
        $langId = $this->localeRepository->getIdByLang($locale);
        BaseRepository::setLang($langId);
        BaseRepository::setLocale($locale);
        $currency = $this->localeRepository->getCurrencyByLang($locale);
        $this->priceFilter->setLocale($locale);
        $this->priceFilter->setCurrency($currency);
        $router = new RouteList();
        $router[] = new ApiRoute('api');
        $router[] = $this->createAdminRouter($locale);
        $router[] = $this->createFrontRouter($locale, $routes);
        return $router;
    }

    private function createFrontRouter(string $locale, array $routes): RouteList
    {
        $localeRoute = "[<locale=$locale>/]";
        $router = new RouteList('Front');
        $router[] = new Route('loginas', 'Homepage:loginas');
        $router[] = new Route('fhb/<action>/<id>', 'Fhb:default');
        $router[] = new Route($localeRoute . 'sitemap.xml', 'Sitemap:default');
        $router[] = new Route('sitemap-[<locale=sk|cz|en|hu>].xml', 'Sitemap:locale');
        $router[] = new Route('sf-callback', 'Homepage:sfCallback');
        $router[] = new Route('monrun', 'Homepage:monrun');
        $router[] = new Route($localeRoute . '404', 'Homepage:error');
        $router[] = new Route($localeRoute . 'update-stock', 'Homepage:updateStock');
        $router[] = new Route($localeRoute . 'update-products', 'Homepage:updateProducts');
        $router[] = new Route($localeRoute . 'trustpay/success', 'Cart:trustpaySuccess');
        $router[] = new Route($localeRoute . 'card/error', 'Cart:trustpayError');
        $router[] = new Route($localeRoute . 'update-exchange-rate', 'Homepage:updateExchangeRate');
        $router[] = new Route($localeRoute . 'card/finish', 'Cart:cardSuccess');
        $router[] = new Route($localeRoute . 'card/notify', 'Cart:cardNotify');
        $router[] = new Route($localeRoute . $routes['search'], 'Search:default');
        $router[] = new Route($localeRoute . $routes['where_to_buy'], 'Homepage:whereToBuy');
        $router[] = new Route($localeRoute . $routes['contact'], 'Homepage:contact');
        $router[] = new Route($localeRoute . $routes['favorite'], 'Account:favorites');
        $router[] = new Route($localeRoute . $routes['login'], 'Sign:in');
        $router[] = new Route($localeRoute . $routes['logout'], 'Sign:out');
        $router[] = new Route($localeRoute . $routes['registration'], 'Sign:up');
        $router[] = new Route($localeRoute . $routes['forgotten_password'], 'Account:lostPassword');
        $router[] = new Route($localeRoute . $routes['new_password'], 'Account:newPassword');
        $router[] = new Route($localeRoute . $routes['sent_password'], 'Account:lostSent');
        $router[] = new Route($localeRoute . $routes['account'], 'Account:default');
        $router[] = new Route($localeRoute . $routes['office'] . '/<action>[/<id>]', 'Office:default');
        $router[] = new Route($localeRoute . $routes['account_detail'], 'Account:order');
        $router[] = new Route($localeRoute . $routes['account_create_user'], 'Account:createUser');
        $router[] = new Route($localeRoute . $routes['account_new_order'], 'Account:newOrder');
        $router[] = new Route($localeRoute . $routes['account_edit'], 'Account:edit');
        $router[] = new Route($localeRoute . $routes['account_unpaid_orders'], 'Account:unpaidOrders');
        $router[] = new Route($localeRoute . $routes['account_paid_orders'], 'Account:paidOrders');
        $router[] = new Route($localeRoute . $routes['account_watchdog'], 'Account:productDog');
        $router[] = new Route($localeRoute . $routes['account_downloads'], 'Account:downloads');
        $router[] = new Route($localeRoute . $routes['blog'], 'Blog:default');
        $router[] = new Route($localeRoute . $routes['gallery'], 'Gallery:default');
        $router[] = new Route($localeRoute . $routes['gallery_images'].'/<slug>', 'Gallery:gallery');
        $router[] = new Route($localeRoute . $routes['marketing_gallery'], 'Account:marketingGallery');
        $router[] = new Route($localeRoute . $routes['marketing_gallery_images'].'/<slug>', 'Account:gallery');
        $router[] = new Route($localeRoute . $routes['cart_summary'], 'Cart:summary');
        $router[] = new Route($localeRoute . $routes['cart_finish'], 'Cart:finish');
        $router[] = new Route($localeRoute . $routes['cart_finish_card'], 'Cart:finishCard');
        $router[] = new Route($localeRoute . $routes['cart'], 'Cart:default');
        $router[] = new Route($localeRoute . $routes['producers'], 'Producer:default');
        $router[] = new Route($localeRoute . $routes['faq'], 'Page:faq');
        $router[] = new Route($localeRoute . $routes['producer'], [
            'presenter' => 'Category',
            'action' => 'producer',
            'slug' => [
                Route::FILTER_IN => function ($slug) {
                    return $this->producerRepository->slugToId($slug);
                },
                Route::FILTER_OUT => function ($slug) {
                    return $this->producerRepository->idToSlug($slug);
                }
            ]
        ]);
        $router[] = new Route($localeRoute . '<slug>', [
            'presenter' => 'Page',
            'action' => 'default',
            'slug' => [
                Route::FILTER_IN => function ($slug) {
                    return $this->pageRepository->slugToId($slug);
                },
                Route::FILTER_OUT => function ($slug) {
                    return $this->pageRepository->idToSlug($slug);
                }
            ]
        ]);
        $router[] = new Route($localeRoute . '<slug>', [
            'presenter' => 'Category',
            'action' => 'default',
            'slug' => [
                Route::FILTER_IN => function ($slug) {
                    return $this->categoryRepository->slugToId($slug);
                },
                Route::FILTER_OUT => function ($slug) {
                    return $this->categoryRepository->idToSlug($slug);
                }
            ]
        ]);
        $router[] = new Route($localeRoute . $routes['blog_post'], [  //'Blog:post'
            'presenter' => 'Blog',
            'action' => 'post',
            'slug' => [
                Route::FILTER_IN => function ($slug) {
                    return $this->blogRepository->slugToId($slug);
                },
                Route::FILTER_OUT => function ($slug) {
                    return $this->blogRepository->idToSlug($slug);
                }
            ]
        ]);
        $router[] = new Route($localeRoute . 'produkt/<slug>', [
            'presenter' => 'Product',
            'action' => 'default',
            'slug' => [
                Route::FILTER_IN => function ($slug) {
                    return $this->productRepository->oldSlugToId($slug);
                }
            ]
        ], $router::ONE_WAY);
        $router[] = new Route($localeRoute . 'kategoria-produktu/<slug>', [
            'presenter' => 'Category',
            'action' => 'default',
            'slug' => [
                Route::PATTERN => '.+',
                Route::FILTER_IN => function ($slug) {
                    return $this->categoryRepository->oldSlugToId($slug);
                }
            ]
        ], $router::ONE_WAY);
        $router[] = new Route($localeRoute . '<slug>', [
            'presenter' => 'Product',
            'action' => 'default',
            'slug' => [
                Route::FILTER_IN => function ($slug) {
                    return $this->productRepository->slugToId($slug);
                },
                Route::FILTER_OUT => function ($slug) {
                    return $this->productRepository->idToSlug($slug);
                }
            ]
        ]);
        $router[] = new Route($localeRoute . '', 'Homepage:default');
        return $router;
    }

    private function createAdminRouter(string $locale): RouteList
    {
        $router = new RouteList('Admin');
        $router->addRoute('admin/kvazynovyadmin', 'Sign:changeAdmin');
        $router->addRoute('admin/<presenter>/<action>/<id>', [
            'presenter' => 'Homepage',
            'action' => 'default',
            'id' => NULL
        ]);
        return $router;
    }

    private function getLocale()
    {
        $url = $this->request->getUrl();
        switch ($url) {
            case Strings::contains($url->absoluteUrl, 'cz.app.mibron.store');
            case Strings::contains($url->absoluteUrl, 'cz.app.localhost.com');
            case Strings::contains($url->absoluteUrl, 'cz.app.mibron.dev');
            case Strings::contains($url->absoluteUrl, 'app.cz');
                return 'cs';
            default:
                return 'sk';
        }
    }

    private function readFile($locale)
    {
        $fileName = __DIR__ . '/route.' . $locale . '.neon';
        if (is_file($fileName)) {
            $file = file_get_contents($fileName);
        } else {
            $file = file_get_contents(__DIR__ . '/route.sk.neon');
        }
        return Neon::decode($file);
    }

}
