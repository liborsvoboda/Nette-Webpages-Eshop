<?php

namespace App\Model\Sitemap;

use DOMDocument;
use DOMElement;
use DateTime;

class Sitemap extends DOMDocument
{
	const DEFAULT_VERSION = '1.0';
	const DEFAULT_ENCODING = 'UTF-8';
	const DEFAULT_XMLNS = 'http://www.sitemaps.org/schemas/sitemap/0.9';
	const DATE_FORMAT = 'Y-m-d';

	/** @var DOMElement */
	private $root;

	/**
	 * Set default version and encoding, force to use static instance generator so the root element is set.
	 * @param string $version
	 * @param string $encoding
	 */
	public function __construct(string $version = self::DEFAULT_VERSION, string $encoding = self::DEFAULT_ENCODING)
	{
		parent::__construct($version, $encoding);
		$this->formatOutput = true;
	}

	/**
	 * Create sitemapindex root sitemap.
	 * @return self
	 */
	public static function createSitemapIndex(): self
	{
		$xml = new self();
		$xml->root = $xml->appendChild($xml->createElement('sitemapindex'));
		$xml->root->setAttribute('xmlns', self::DEFAULT_XMLNS);
		return $xml;
	}

	/**
	 * Create urlset sitemap.
	 * @return self
	 */
	public static function createUrlSet(): self
	{
		$xml = new self();
		$xml->root = $xml->appendChild($xml->createElement('urlset'));
		$xml->root->setAttribute('xmlns', self::DEFAULT_XMLNS);
		return $xml;
	}

	/**
	 * Append sitemap child element.
	 * @param string $loc Absolute URL of nested sitemap.
	 * @param DateTime|NULL $lastmod When was the nested sitemap last modified? NULL - current date.
	 * @return DOMElement
	 */
	public function appendSitemap(string $loc, DateTime $lastmod = NULL): DOMElement
	{
		if ($lastmod === NULL) $lastmod = new DateTime();
		$sitemap = $this->root->appendChild($this->createElement('sitemap'));
		$sitemap->appendChild($this->createElement('loc', $loc));
		$sitemap->appendChild($this->createElement('lastmod', $lastmod->format(self::DATE_FORMAT)));

		return $sitemap;
	}

	/**
	 * Append url element.
	 * @param string $loc URL location.
	 * @param DateTime|NULL $lastmod When was the URL last modified? NULL - current data.
	 * @param float $priority
	 * @param string $changefreq yearly|monthly|weekly|daily|always
	 * @return DOMElement
	 */
	public function appendUrl(string $loc, DateTime $lastmod = null, float $priority = 0.5, string $changefreq = 'always')
	{
		if ($lastmod === NULL) $lastmod = new DateTime();
		$url = $this->root->appendChild($this->createElement('url'));
		$url->appendChild($this->createElement('loc', $loc));
		$url->appendChild($this->createElement('lastmod', $lastmod->format(self::DATE_FORMAT)));
		$url->appendChild($this->createElement('priority', $priority));
		$url->appendChild($this->createElement('changefreq', $changefreq));

		return $url;
	}
}