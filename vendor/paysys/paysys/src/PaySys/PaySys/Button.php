<?php

namespace PaySys\PaySys;

use Nette;
use Nette\Application\UI\Control;


/**
 * @property Nette\Application\UI\ITemplate $template
 * @method void onBeforePayRequest(IPayment $payment)
 * @method void onPayRequest(IPayment $payment)
 */
class Button extends Control
{

	/** @var callable[]  function (IPayment $payment); Occurs before pay request */
	public $onBeforePayRequest;

	/** @var callable[]  function (IPayment $payment); Occurs on pay request */
	public $onPayRequest;


	/** @var IPayment */
	private $payment;

	/** @var IConfiguration */
	private $config;


	public function __construct(IPayment $payment, IConfiguration $config)
	{
		$this->payment = $payment;
		$this->config = $config;
	}

	public function render()
	{
		$template = $this->template;
		$template->setFile($this->config->getButtonTemplate());
		$template->render();
	}

	public function handlePay()
	{
		$this->onBeforePayRequest($this->payment);
		$this->onPayRequest($this->payment);
	}
}
