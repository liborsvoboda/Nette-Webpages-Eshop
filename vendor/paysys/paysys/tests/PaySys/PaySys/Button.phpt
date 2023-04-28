<?php

use PaySys\PaySys\Button;
use PaySys\PaySys\IConfiguration;
use PaySys\PaySys\IPayment;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$config = new class implements IConfiguration {
	function getButtonTemplate() : string {}
};

$payment = new class implements IPayment
{
	public function setAmount($amt) : IPayment {}

	public function getAmount() : string {}
};

$button = new Button($payment, $config);

Assert::true($button instanceof Button);

$button->handlePay();
