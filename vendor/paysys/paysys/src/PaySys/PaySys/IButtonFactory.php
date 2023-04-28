<?php

namespace PaySys\PaySys;


interface IButtonFactory
{
	public function create(IPayment $payment) : Button;
}
