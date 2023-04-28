<?php

namespace App\Model\Menu;

use App\Model\LocaleRepository;

class MenuFacade {

    private $langId = 1;
    private $localeRepository;
    private $menuRepository;

    public function __construct(LocaleRepository $localeRepository, MenuRepository $menuRepository) {
        $this->localeRepository = $localeRepository;
        $this->menuRepository = $menuRepository;
    }

    public function getAllJson($locale) {
        $this->setLocale($locale);

        $menuItems = $this->menuRepository->getAll($this->langId);
        $menuItemsArray = [];
        foreach ($menuItems as $menuItem) {
            $menuItemsArray[] = ['id' => $menuItem->id, 'title' => $menuItem->title,'slug'=>$menuItem->slug,'position'=>$menuItem->position];
        }
        return $menuItemsArray;
    }
    
    private function setLocale($locale)
    {
        $loc = $this->localeRepository->getLocaleByCountryCode($locale)->fetch();
        if($loc) {
            $this->langId = $loc->lang_id;
        }
    }

}
