<?php


namespace App\AdminModule\Components\Gallery;


interface IGalleryImageGridFactory
{

    public function create(): MarketingGalleryImageGrid;

}