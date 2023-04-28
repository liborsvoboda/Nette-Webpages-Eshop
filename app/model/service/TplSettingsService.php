<?php

namespace App\Model\Services;

use App\Model\BaseRepository;
use App\Model\Setting\SettingRepository;
use Contributte\Translation\LocalesResolvers\Session as TranslationSession;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Nette\Localization\ITranslator;
use Nette\MemberAccessException;

class TplSettingsService {

	/** @var ArrayHash */
	private $config;

	/** @var ITranslator */
	private $translator;

	/** @var TranslationSession */
	private $translatorSessionResolver;

	/** @var SettingRepository */
	private $settingRepository;

	/**
	 * @param array $config
	 * @param ITranslator $translator
	 * @param TranslationSession $translatorSessionResolver
	 * @param SettingRepository $settingRepository
	 */
	public function __construct(
		array $config,
		ITranslator $translator,
		TranslationSession $translatorSessionResolver,
		SettingRepository $settingRepository
	) {
		$this->config = ArrayHash::from($config);
		$this->translator = $translator;
		$this->translatorSessionResolver = $translatorSessionResolver;
		$this->settingRepository = $settingRepository;
	}

	/**
	 * Get locale string (ISO 639-1 format).
	 * @return string
	 * @see https://www.w3schools.com/tags/ref_language_codes.asp
	 */
	public function getLocale(): string
	{
		$locale = BaseRepository::getLocale();
		return ($locale === NULL) ? $this->getDefaultLocale() : $locale;
	}

	/**
	 * @param string $relativePath
	 * @param bool $withVersion
	 * @return string
	 */
	public function getAssetPath(string $relativePath, bool $withVersion = true): string
	{
		$path = '/assets/front/build/' . ($this->getDistAssets() ? 'min' : 'dev') . '/';
		$path .= $relativePath;
		if ($withVersion) $path .= '?v=' . $this->getAssetsVersion();
		return $path;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function getSvg(string $name): string {
		$name = str_replace('.svg', '', $name);
		$path = '/assets/front/svg/' . $name . '.svg';
		return $path;
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	public function getImg(string $filename): string {
		$path = '/assets/front/img/' . $filename;
		return $path;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getSetting(string $key)
	{
		return $this->settingRepository->getValue($key);
	}

	/**
	 * @return array|string[]
	 */
	public function getSocials(): ArrayHash
	{
		return ArrayHash::from(array_filter((array)$this->config->socials));
	}

	/**
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function __call(string $name, array $args)
	{
		if (preg_match('/^get([A-Z]{1}.*)$/', $name, $match)) {
			$prop = Strings::firstLower($match[1]);
			if (isset($this->config[$prop])) return $this->config[$prop];
		}
		throw new MemberAccessException("Method '$name' doesn't exist.");
	}
}