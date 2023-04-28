<?php

namespace App\Model\Sitemap;

use App\Model\Category\CategoryRepository;
use App\Model\LocaleRepository;
use App\Model\Product\ProductRepository;
use App\Model\Blog\BlogRepository;
use App\Model\Page\PageRepository;
use App\Model\Lang\LangRepository;
use Nette\Application\LinkGenerator;
use Nette\Database\Table\ActiveRow;
use InvalidArgumentException;
use Nette\Utils\ArrayHash;

class SitemapService
{

	/** @var CategoryRepository */
	private $categoryRepository;

	/** @var ProductRepository */
	private $productRepository;

	/** @var BlogRepository */
	private $blogRepository;

	/** @var PageRepository */
	private $pageRepository;

	/** @var LocaleRepository */
	private $localeRepository;

	/** @var LinkGenerator */
	private $linkGenerator;

	/** @var Sitemap */
	private $sitemap;

	/** @var ArrayHash */
	private $lang;

	public function __construct(
		CategoryRepository $categoryRepository,
		ProductRepository $productRepository,
		BlogRepository $blogRepository,
		PageRepository $pageRepository,
		LocaleRepository $localeRepository,
		LinkGenerator $linkGenerator
	) {
		$this->categoryRepository = $categoryRepository;
		$this->productRepository = $productRepository;
		$this->blogRepository = $blogRepository;
		$this->pageRepository = $pageRepository;
		$this->localeRepository = $localeRepository;
		$this->linkGenerator = $linkGenerator;
		$this->lang = new ArrayHash();
	}

	/**
	 * Generate root sitemap with links to language sitemaps.
	 * @return Sitemap
	 */
	public function generateRoot(): Sitemap
	{
		$this->sitemap = Sitemap::createSitemapIndex();

		$locales = $this->localeRepository->getByLang('sk');
		foreach ($locales as $locale) {
			$this->sitemap->appendSitemap($this->linkGenerator->link('Front:Sitemap:locale', ['locale' => $locale->lang->locale]));
		}

		return $this->sitemap;
	}

	/**
	 * Generate sitemap for given locale code.
	 * @param string $locale
	 * @return Sitemap
	 *
	 * @throws InvalidArgumentException
	 */
	public function generateLocale(string $locale): Sitemap
	{
        $loc = $this->localeRepository->getByLang($locale)->fetch();
		$this->lang->iso = $locale;
		$this->lang->id = $loc->lang_id;

		$this->sitemap = Sitemap::createUrlSet();
		$this->addHomepageItem();
		$this->addProductItems();
		$this->addCategoryItems();
		$this->addPageItems();
		$this->addBlogItems();
		return $this->sitemap;
	}

	/**
	 * Add sitemap URL of homepage in current language.
	 * @param float $priority
	 * @param string $changefreq yearly|monthly|weekly|daily|always
	 */
	public function addHomepageItem(float $priority = 0.9, string $changefreq = 'always')
	{
		$this->sitemap->appendUrl(
			$this->linkGenerator->link('Front:Homepage:default', ['locale' => $this->lang->iso]),
			NULL,
			$priority,
			$changefreq
		);
	}

	/**
	 * Add sitemap URLs of all products with current language available.
	 * @param float $priority
	 * @param string $changefreq yearly|monthly|weekly|daily|always
	 */
	private function addProductItems(float $priority = 0.8, string $changefreq = 'always')
	{
		$this->productRepository->setLang($this->lang->id);
		$items = $this->productRepository->getAll();

		foreach ($items as $item) {
			$this->sitemap->appendUrl(
				$this->linkGenerator->link('Front:Product:default', ['locale' => $this->lang->iso, 'slug' => $item->id]),
				NULL,
				$priority,
				$changefreq
			);
		}
	}

	/**
	 * Add sitemap URLs of categories with current language available.
	 * @param float $priority
	 * @param string $changefreq yearly|monthly|weekly|daily|always
	 */
	private function addCategoryItems(float $priority = 0.8, string $changefreq = 'always')
	{
		$this->categoryRepository->setLang($this->lang->id);
		$items = $this->categoryRepository->getAll()->fetchAll();

		foreach ($items as $item) {
			$this->sitemap->appendUrl(
				$this->linkGenerator->link('Front:Category:default', ['locale' => $this->lang->iso, 'slug' => $item->id]),
				NULL,
				$priority,
				$changefreq
			);
		}
	}

	/**
	 * Add sitemap URLs of pages with current language available.
	 * @param float $priority
	 * @param string $changefreq yearly|monthly|weekly|daily|always
	 */
	private function addPageItems(float $priority = 0.5, string $changefreq = 'weekly')
	{
		$this->pageRepository->setLang($this->lang->id);
		$items = $this->pageRepository->getAll();

		foreach ($items as $item) {
			$this->sitemap->appendUrl(
				$this->linkGenerator->link('Front:Page:default', ['locale' => $this->lang->iso, 'slug' => $item->id]),
				NULL,
				$priority,
				$changefreq
			);
		}
	}

	/**
	 * Add sitemap URLs of blog articles with current language available.
	 * @param float $priority
	 * @param string $changefreq yearly|monthly|weekly|daily|always
	 */
	private function addBlogItems(float $priority = 0.5, string $changefreq = 'weekly')
	{
		$this->blogRepository->setLang($this->lang->id);
		$items = $this->blogRepository->getAll();

		$this->sitemap->appendUrl(
			$this->linkGenerator->link('Front:Blog:default', ['locale' => $this->lang->iso]),
			NULL,
			$priority,
			$changefreq
		);

		foreach ($items as $item) {
			$this->sitemap->appendUrl(
				$this->linkGenerator->link('Front:Blog:post', ['locale' => $this->lang->iso, 'slug' => $item->id]),
				NULL,
				$priority,
				$changefreq
			);
		}
	}
}