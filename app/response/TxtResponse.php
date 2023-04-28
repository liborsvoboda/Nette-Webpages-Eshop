<?php

namespace App\Responses;

use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Application\IResponse as AppIResponse;
use DOMDocument;

final class TxtResponse implements AppIResponse
{
	/** @var string */
	private $content;

	/** @var string */
	private $filename;

	/** @var string */
	private $contentDescription;

	public function __construct(
		string $content,
		string $filename = 'file.txt',
		string $contentDescription = ''
	) {
		$this->content = $content;
		$this->filename = $filename;
		$this->contentDescription = $contentDescription;
	}

	/**
	 * {@inheritdoc}
	 */
	public function send(IRequest $request, IResponse $response): void
	{
		$response->setContentType('text/plain');
		$response->setExpiration(FALSE);
		$response->setHeader('Content-Disposition', 'inline;filename="' . $this->filename . '"');
		if ($this->contentDescription) $response->setHeader('Content-Description', $this->contentDescription);

		echo $this->content;
	}
}