<?php declare(strict_types = 1);

namespace Apitte\Presenter;

use Apitte\Core\Application\IApplication;
use Apitte\Core\Http\ApiRequest;
use Contributte\Psr7\Psr7ServerRequestFactory;
use Contributte\Psr7\Psr7Uri;
use Nette\Application\IPresenter;
use Nette\Application\IResponse;
use Nette\Application\Request;
use Nette\Application\Responses\CallbackResponse;
use Nette\Http\Request as HttpRequest;

class ApiPresenter implements IPresenter
{

	/** @var IApplication */
	private $application;

	/** @var HttpRequest */
	private $request;

	public function __construct(IApplication $application, HttpRequest $request)
	{
		$this->application = $application;
		$this->request = $request;
	}

	public function run(Request $request): IResponse
	{
		$url = $this->request->getUrl();
		$url = substr($url->getPath(), strlen($url->getScriptPath()));

		$psrRequest = Psr7ServerRequestFactory::fromNette($this->request)
			->withUri(new Psr7Uri($url));
		$psrRequest = new ApiRequest($psrRequest);

		return new CallbackResponse(function () use ($psrRequest): void {
			$this->application->runWith($psrRequest);
		});
	}

}
