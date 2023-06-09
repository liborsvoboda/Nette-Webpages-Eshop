<?php declare(strict_types = 1);

namespace Contributte\Psr7;

use Nette\Http\IRequest;
use Nette\Http\RequestFactory;
use function GuzzleHttp\Psr7\stream_for;

class Psr7RequestFactory
{

	public static function fromGlobal(): Psr7Request
	{
		$requestFactory = new RequestFactory();

		return self::fromNette($requestFactory->createHttpRequest());
	}

	public static function fromNette(IRequest $request): Psr7Request
	{
		$psr7 = new Psr7Request(
			$request->getMethod(),
			Psr7UriFactory::fromNette($request->getUrl()),
			$request->getHeaders(),
			stream_for($request->getRawBody()),
			str_replace('HTTP/', '', $request->getHeader('SERVER_PROTOCOL') ?? '1.1')
		);

		// Nette-compatibility
		$psr7 = $psr7->withHttpRequest($request);

		return $psr7;
	}

}
