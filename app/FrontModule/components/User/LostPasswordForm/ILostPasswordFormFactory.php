<?php


namespace App\FrontModule\Components\User;


interface ILostPasswordFormFactory
{

    public function create(): LostPasswordForm;

}
