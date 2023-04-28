<?php


namespace App\AdminModule\Components\Attribute;


interface IAttributeValueGridFactory
{

    public function create(int $attributeId): AttributeValueGrid;

}
