<?php


namespace App\AdminModule\Components\EmailStatus;


interface IEmailStatusFormFactory
{

    public function create(): EmailStatusForm;

}
