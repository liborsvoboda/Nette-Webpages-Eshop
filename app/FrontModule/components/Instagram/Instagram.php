<?php

namespace App\FrontModule\Components\Instagram;

use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Localization\ITranslator;
// use Instagram\Api;

class Instagram extends Control
{

	/** @var string */
	private $userName;

	/** @var AppSettingsService */
	private $appSettingsService;

	/** @var ITranslator */
	private $translator;
	
	/**
	 * @param string $userName
	 */
	public function __construct(
		string $userName,
		AppSettingsService $appSettingsService,
		ITranslator $translator
	) {
		$this->userName = $userName;
		$this->appSettingsService = $appSettingsService;
		$this->translator = $translator;
	}

	public function render()
	{
		// $api = new Api();
		// $api->setUserName($this->userName);
		// $this->template->feed = $api->getFeed();

		$this->template->setTranslator($this->translator);
		$this->template->userName = $this->userName;
		$this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/Instagram/instagram.latte');
	}
}