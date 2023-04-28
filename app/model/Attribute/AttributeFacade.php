<?php

namespace App\Model\Attribute;

use App\Model\LocaleRepository;

class AttributeFacade {
    
    private $langId = 1;
    
    private $localeRepository;
    private $attributeRepository;

    public function __construct(LocaleRepository $localeRepository, AttributeRepository $attributeRepository)
    {
        $this->localeRepository = $localeRepository;
        $this->attributeRepository = $attributeRepository;
    }
    
    public function getAllJson($locale){
        $this->setLocale($locale);
        $attributes = $this->attributeRepository->getAll($this->langId);
        $attrbuteArray = [];
        foreach($attributes as $attribute){
            $attrbuteArray[] = [
                'id'=>$attribute->id,
                'name'=>$attribute->name,
                'filterable'=>$attribute->filterable,
                'searchable'=>$attribute->searchable,
                'visible'=>$attribute->visible
                    ];
        }
        return $attrbuteArray;
    }
    
    private function setLocale($locale)
    {
        $loc = $this->localeRepository->getLocaleByCountryCode($locale)->fetch();
        if($loc) {
            $this->langId = $loc->lang_id;
        }
    }
}
