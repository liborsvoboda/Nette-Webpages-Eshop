<?php

namespace App\Responses;

use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Application\IResponse as AppIResponse;
use DOMDocument;

final class XMLResponse implements AppIResponse
{

	/** @var DOMDocument */
	private $xml;

	/** @var string */
	private $contentDescription;

	/**
	 * @param DOMDocument $xml DOMDocument to be sent.
	 * @param string $contentDescription Content-Description header.
	 */
	public function __construct(
		DOMDocument $xml,
		string $contentDescription = ''
	) {
		$this->xml = $xml;
		$this->contentDescription = $contentDescription;
	}

	/**
	 * {@inheritdoc}
	 */
	public function send(IRequest $request, IResponse $response): void
	{
		$response->setContentType('text/xml');
		$response->setExpiration(FALSE);
		if ($this->contentDescription) $response->setHeader('Content-Description', $this->contentDescription);

		echo $this->xml->saveXML();
	}
}