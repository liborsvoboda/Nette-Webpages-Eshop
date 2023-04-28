<?php


namespace App\AdminModule\Components\Gallery;


interface IGalleryGridFactory
{

    public function create(): MarketingGalleryGrid;

}