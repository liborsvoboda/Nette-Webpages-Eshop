<?php


namespace App\FrontModule\Components\User;


interface ISignInFormFactory
{

    public function create(): SignInForm;

}
