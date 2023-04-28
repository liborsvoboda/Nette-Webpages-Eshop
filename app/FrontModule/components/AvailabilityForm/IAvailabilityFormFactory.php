<?php

namespace App\FrontModule\Components\AvailabilityForm;

interface IAvailabilityFormFactory
{

    public function create(int $productId): AvailabilityForm;

}
