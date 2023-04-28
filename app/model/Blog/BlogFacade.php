<?php

namespace App\Model\Blog;

use App\Model\LocaleRepository;

class BlogFacade {

    private $langId = 1;
    private $localeRepository;
    private $blogRepository;

    public function __construct(LocaleRepository $localeRepository, BlogRepository $blogRepository) {
        $this->localeRepository = $localeRepository;
        $this->blogRepository = $blogRepository;
    }
    
     public function getAllJson($locale) {
        $this->setLocale($locale);
        $blogPages = $this->blogRepository->getAll($this->langId);
        $blogPagesArray = [];
        foreach ($blogPages as $blogPage) {
            $blogPagesArray[] = $this->getBlogPageArray($blogPage);
        }
        return $blogPagesArray;
    }
    
     public function getDetailJson($locale, $id)
    {
        $this->setLocale($locale);
        $page = $this->blogRepository->getBySlug($this->langId)->fetch();
        $pageArray = [];
        if ($page) {
            $pageArray = $this->getBlogPageArray($page);
        }

        return $pageArray;
    }

    private function getBlogPageArray($blogPage) {
        return [
            'id' => $blogPage->id,
            'title' => $blogPage->title,
            'text' => $blogPage->text,
            'slug' => $blogPage->slug
        ];
    }

    private function setLocale($locale) {
        $loc = $this->localeRepository->getLocaleByCountryCode($locale)->fetch();
        if ($loc) {
            $this->langId = $loc->lang_id;
        }
    }

}
