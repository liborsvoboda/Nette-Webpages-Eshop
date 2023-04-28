<?php

namespace App\FrontModule\Presenters;

use App\Model\Sitemap\SitemapService;
use App\Responses\XMLResponse;
use Nette\Application\UI\Presenter;

class SitemapPresenter extends Presenter {

	/**
	 * @var SitemapService
	 * @inject
	 */
	public $sitemapService;

	/**
	 * Generate root sitemap with locale links.
	 */
	public function actionDefault()
	{
		$sitemap = $this->sitemapService->generateRoot();
		$this->sendResponse(new XMLResponse($sitemap));
	}

	/**
	 * Generate sitemap for given locale code.
	 * @param string $locale
	 */
	public function actionLocale(string $locale)
	{
		$sitemap = $this->sitemapService->generateLocale($locale);
		$this->sendResponse(new XMLResponse($sitemap));
	}
}