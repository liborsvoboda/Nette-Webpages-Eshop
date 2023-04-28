<?php


namespace App\FrontModule\Components\User;


interface IRegisterFormFactory
{

    public function create(string $locale): RegisterForm;

}
