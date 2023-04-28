<?php

namespace PaySys\PaySys;


interface IPayment
{
	public function setAmount($amt) : IPayment;

	public function getAmount() : string;
}
