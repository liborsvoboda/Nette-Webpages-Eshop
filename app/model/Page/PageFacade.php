<?php

namespace App\Model\Page;

use App\Model\LocaleRepository;

class PageFacade {

    private $langId = 1;
    private $localeRepository;
    private $pageRepository;

    public function __construct(LocaleRepository $localeRepository, PageRepository $pageRepository) {
        $this->localeRepository = $localeRepository;
        $this->pageRepository = $pageRepository;
    }

    public function getAllJson($locale) {
        $this->setLocale($locale);
        $pages = $this->pageRepository->getAll($this->langId);
        $pagesArray = [];
        foreach ($pages as $page) {
            $pagesArray[] = $this->getPageArray($page);
        }
        return $pagesArray;
    }
    
     public function getDetailJson($locale, $id)
    {
        $this->setLocale($locale);
        $page = $this->pageRepository->getBySlug($id)->fetch();
        $pageArray = [];
        if ($page) {
            $pageArray = $this->getPageArray($page);
        }

        return $pageArray;
    }

    private function getPageArray($page) {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'text' => $page->text,
            'slug' => $page->slug
        ];
    }

    private function setLocale($locale) {
        $loc = $this->localeRepository->getLocaleByCountryCode($locale)->fetch();
        if ($loc) {
            $this->langId = $loc->lang_id;
        }
    }

}
