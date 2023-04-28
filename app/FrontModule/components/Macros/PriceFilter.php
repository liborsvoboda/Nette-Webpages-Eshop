<?php

namespace App\Components\Macros;

use NumberFormatter;

class PriceFilter
{
	/** @var string */
	private $currency;

    /**
     * @var string */
	private $locale;

	/** @var NumberFormatter */
	private $formatter;

	/**
	 * @param string $currency Currency in ISO 4217
	 * @param string $locale
	 */
	public function __construct(
		string $currency,
		string $locale
	) {
		$this->currency = $currency;
		$this->locale = $locale;
	}

	/**
	 * @param float $price
	 * @param string $currency Currency in ISO 4217 to overwrite default currency.
	 * @return string
	 */
	public function price(?float $price, string $currency = null): string
	{
		$currency ?: $this->currency;
		return $this->formatter->formatCurrency($price, $this->currency);
	}

    public function setCurrency($currency)
    {
        $this->currency = $currency;
	}

    public function setLocale($locale)
    {
        $this->locale = $locale;
        $this->formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
	}
}