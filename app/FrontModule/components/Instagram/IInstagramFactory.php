<?php

namespace App\FrontModule\Components\Instagram;

interface IInstagramFactory {

    /**
     * @param string $userName
     * @return Instagram
     */
    public function create(string $userName): Instagram;
}