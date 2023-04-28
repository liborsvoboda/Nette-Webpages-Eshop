<?php


namespace App\FrontModule\Components\Cart;

use Nette\Database\Table\ActiveRow;

interface ICartAddFormFactory
{

    public function create(ActiveRow $product): CartAddForm;

}
