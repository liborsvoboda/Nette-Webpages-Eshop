<?php

namespace App\FrontModule\Components\ContactForm;

interface IContactFormFactory
{

    public function create(): ContactForm;

}
