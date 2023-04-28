<?php


namespace App\FrontModule\Components\User;


interface INewPasswordFormFactory
{

    public function create(): NewPasswordForm;

}
