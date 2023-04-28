<?php declare(strict_types = 1);

namespace Contributte\Psr7;

use Nette\Http\IRequest;
use Nette\Http\RequestFactory;
use function GuzzleHttp\Psr7\stream_for;

class Psr7ServerRequestFactory
{

	public static function fromSuperGlobal(): Psr7ServerRequest
	{
		return Psr7ServerRequest::fromGlobals();
	}

	public static function fromGlobal(): Psr7ServerRequest
	{
		$requestFactory = new RequestFactory();

		return self::fromNette($requestFactory->createHttpRequest());
	}

	public static function fromNette(IRequest $request): Psr7ServerRequest
	{
		$psr7 = new Psr7ServerRequest(
			$request->getMethod(),
			Psr7UriFactory::fromNette($request->getUrl()),
			$request->getHeaders(),
			stream_for($request->getRawBody()),
			str_replace('HTTP/', '', $request->getHeader('SERVER_PROTOCOL') ?? '1.1'),
			$_SERVER
		);

		$psr7 = $psr7->withCookieParams($request->getCookies())
			->withQueryParams($request->getQuery())
			->withParsedBody($request->getPost())
			->withUploadedFiles(Psr7ServerRequest::normalizeNetteFiles($request->getFiles()));

		// Nette-compatibility
		$psr7 = $psr7->withHttpRequest($request);

		return $psr7;
	}

}
