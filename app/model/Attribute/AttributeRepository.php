<?php


namespace App\Model\Attribute;


use App\Model\BaseRepository;
use App\Model\LocaleRepository;
use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;
use function Symfony\Component\String\b;

class AttributeRepository extends BaseRepository
{
    const ATTRIBUTE_TYPE_INPUT = 1,
        ATTRIBUTE_TYPE_SELECT = 2,
        ATTRIBUTE_TYPE_MULTI_SELECT = 3;

    private $attributeTypes = [
        self::ATTRIBUTE_TYPE_INPUT => 'Text',
        self::ATTRIBUTE_TYPE_SELECT => 'Select',
        self::ATTRIBUTE_TYPE_MULTI_SELECT => 'MultiSelect'
    ];

    private $table = 'attribute';

    protected $db, $localeRepository;

    public function __construct(Context $db, LocaleRepository $localeRepository)
    {
        $this->db = $db;
        $this->localeRepository = $localeRepository;
    }

    public function getAll($localeId = null): Selection
    {
        $localeId = $localeId ?? $this->localeId();
        $out = $this->db->table($this->table)
            ->select(':attribute_lang.*, attribute.*, attribute.id AS attributeId')
            ->where(':attribute_lang.locale_id', $localeId);
        return $out;
    }

    public function getAllAsIdAndNameArray($localeId = null)
    {
        return$this->getAll($localeId)->fetchPairs('id', 'name');
    }

    public function getById($id)
    {
        return $this->db->table($this->table)->where('id', $id);
    }

    public function getAllToSelect($localeId = null)
    {
        $out = $this->getAll($localeId)->fetchPairs('attribute_id', 'name');
        return $out;
    }

    public function getValuesToSelect($attributeId, $localeId = null)
    {
        $attrValues = $this->db->table('attribute_value')
            ->where('attribute_id', $attributeId)
            ->fetchPairs('id', 'id');
        $values = $this->db->table('attribute_value_lang')
            ->where('attribute_value_id', $attrValues)
            ->where('locale_id', $localeId)
            ->fetchPairs('attribute_value_id', 'value');
        return $values;
    }

    public function getIdByName($name)
    {
        $out = $this->getAll()->where(':attribute_lang.name', $name)->fetch();
        return $out ? $out->id : null;
    }

    public function getNameById($id, $localeId = null)
    {
        $name = $this->getAll($localeId)->where('attribute.id', $id)->fetch();
        return $name ? $name->name : '';
    }

    public function add($name, $filterable = true, $langId)
    {
        $aId = $this->db->table('attribute')->insert(['type' => self::ATTRIBUTE_TYPE_INPUT, 'filterable' => $filterable, 'searchable' => true]);
        $this->db->table('attribute_lang')->insert(['lang_id' => $langId, 'attribute_id' => $aId, 'name' => $name]);
    }

    public function getAttributeType($id)
    {
        return $this->attributeTypes[$id];
    }

    public function getProductAttributes($productId): array
    {
        $productAttributes = $this->db->table('product_attribute')->where('product_id', $productId)->fetchPairs('attribute_id', 'attribute_value_id');
        return $productAttributes;
    }

    public function getProductAttributeValues($productId, $attributeId): array
    {
        $productAttributes = $this->db->table('product_attribute')
            ->where('product_id', $productId)
            ->where('attribute_id', $attributeId)
            ->fetchPairs(null, 'attribute_value_id');
        return $productAttributes;
    }

    public function getParentVariantsCount($parentId, $localeId = null)
    {
        $localeId = $localeId ?? $this->localeId();
        $subs = $this->db->table('product')->where('parent_id', $parentId)->fetchPairs('id', 'id');
        $count = $this->db->table('product_attribute')
            ->where('product_id', $subs)
            ->where('locale_id', $localeId)
            ->max('position');
        return $count;
    }

    public function getParentVariantsIds($parentId, $localeId = null)
    {
        $localeId = $localeId ?? $this->localeId();
        $subs = $this->db->table('product')->where('parent_id', $parentId)->fetchPairs('id', 'id');
        $out = $this->db->table('product_attribute')
            ->select('DISTINCT attribute_id')
            ->where('product_id', $subs)
            ->order('position')
            ->fetchPairs(null, 'attribute_id');
        return $out;
    }

    public function getParentAttributeName($parentId, $position, $localeId = null)
    {
        $localeId = $localeId ?? $this->localeId();
        $subs = $this->db->table('product')->where('parent_id', $parentId)->fetchPairs('id', 'id');
        $attribute = $this->db->table('product_attribute')->where('product_id', $subs)->where('position', $position)->fetch();
        $name = $this->db->table('attribute_lang')->where('attribute_id', $attribute->attribute_id)->where('locale_id', $localeId)->fetch();
        return $name->name;
    }

    public function getProductAttributesLang($productId, $localeId = null): array
    {
        $localeId = $localeId ?? $this->localeId();
        $attributesLang = [];
        $attributeIds = $this->db->table('product_attribute')
            ->where('product_id', $productId)
            ->order('position')
            ->fetchAll();
        if (!$attributeIds) {
            return $attributesLang;
        }
        foreach ($attributeIds as $attributeId) {
            $value = $this->db->table('attribute_value_lang')
                ->where('locale_id', $localeId)
                ->where('attribute_value_id', $attributeId->attribute_value_id)
                ->fetch();
            $attributesLang[$attributeId->attribute_id] = $value->value;
        }
        return $attributesLang;
    }

    public function getProductAttributesId($productId, $localeId = null): array
    {
        $localeId = $localeId ?? $this->localeId();
        $attributesId = [];
        $attributeIds = $this->db->table('product_attribute')
            ->where('product_id', $productId)
            ->order('position')
            ->fetchAll();
        if (!$attributeIds) {
            return $attributesId;
        }
        foreach ($attributeIds as $attributeId) {
            $value = $this->db->table('attribute_value_lang')
                ->where('locale_id', $localeId)
                ->where('attribute_value_id', $attributeId->attribute_value_id)
                ->fetch();
            $attributesId[$attributeId->attribute_id] = $value->id;
        }
        return $attributesId;
    }

    public function getAttributesForParent($parentId, $position, $selected = [], $localeId = null)
    {
        $localeId = $localeId ?? $this->localeId();
        if ($selected === null) {
            return [];
        }
        $subs = $this->db->table('product')->where('parent_id', $parentId)->fetchPairs('id', 'id');
        $rest = $this->db->table('product_attribute')
            ->where('product_id', $subs)
            ->where('position', $position - 1)
            ->where('attribute_value_id', $selected)
            ->fetchPairs('product_id', 'product_id');
        $choose = $position == 1 ? $subs : $rest;
        $attributeValueIds = $this->db->table('product_attribute')
            ->select('DISTINCT attribute_value_id')
            ->where('product_id', $choose)
            ->where('position', $position)
            ->fetchPairs('attribute_value_id', 'attribute_value_id');
        $attributes = $this->db->table('attribute_value_lang')
            ->where('attribute_value_id', $attributeValueIds)
            ->where('locale_id', $this->localeId())
            ->fetchPairs('attribute_value_id', 'value');
        return $attributes;
    }

    public function getAttributeValues($attributeId, $localeId = null)
    {
        $localeId = $localeId ?? $this->localeId();
        $values = $this->db->table('attribute_value')
//            ->select('DISTINCT attribute_id, attribute_value_id')
            ->where('attribute_id', $attributeId)
            ->fetchAll();
        if (count($values) == 0) {
            return null;
        }
        $out = $this->db->table('attribute_value_lang')
            ->where('attribute_value_id', $values)
            ->where('locale_id', $localeId)
            ->fetchPairs('attribute_value_id', 'value');
        return $out;
    }

    public function getAttributeValueLang($id, $localeId)
    {
        return $this->db->table('attribute_value_lang')
            ->where('id', $id)
            ->where('locale_id', $localeId)
            ->fetch();
    }

    public function getAttributesToFilter($products)
    {
        $currencyId = $this->localeRepository->getCurrencyIdByLangId($this->langId());
		$attrs = $this->db->table('product_attribute')
			->select('DISTINCT attribute_id, attribute_value_id')
			->where('product_id', $products)
			->fetchAll();

		$filters = [];
		foreach ($attrs as $attr) {
			$name = $attr->attribute_value->related('attribute_value_lang')->where('locale_id', $currencyId)->fetch();
			if ($name) $filters[$attr->attribute_id][$name->attribute_value_id] = $name->value;
		}
		return $filters;
    }

    public function getProductAttributesAsArray($productId): ArrayHash
    {
        $out = new ArrayHash();
        $attributes = $this->db->table('attribute')
            ->select('attribute.*, :attribute_lang.name, :attribute_lang.attribute_id')
            ->where(':attribute_lang.locale_id', $this->localeId())
            ->where('visible', true)
            ->fetchAll();
        $productAttributes = $this->db->table('product_attribute')
            ->where('product_id', $productId)
            ->fetch();
        if (!$productAttributes) {
            return $out;
        }
        // $attrbuteValues = $this->db->table('');
        foreach ($attributes as $attribute) {
            $out[$attribute->name] = isset($productAttributes[$attribute->attribute_id]) ? $productAttributes[$attribute->attribute_id] : '';
        }
        return $out;
    }

    public function remove($id)
    {
        $this->db->table('product_attribute')->where('attribute_id', $id)->delete();
        $this->db->table('attribute_lang')->where('attribute_id', $id)->delete();
        $this->db->table('attribute')->where('id', $id)->delete();
    }


    public function updateProductAttributes($values, $productId)
    {
        $position = 1;
        foreach ($values as $key => &$value) {
            if (strpos($key, 'attrv') !== false) {
                continue;
            }
            if (strpos($key, 'attr') !== false) {
                if (strlen($value) > 0) {
                    $avalue = $this->getAttributeValueLang($values['attrv' . $position], 1);
                    $productAttributes = [
                        'product_id' => $productId,
                        'attribute_id' => $value,
                        'position' => $position
                    ];
                    $test = $this->db->table('product_attribute')
                        ->where($productAttributes)
                        ->fetch();
//                        dump($test);die;
                    if ($test) {
                        $this->db->table('product_attribute')
                            ->where($productAttributes)
                            ->update(['attribute_value_id' => $avalue->attribute_value_id]);
                    } else {
                        $productAttributes['attribute_value_id'] = $avalue->attribute_value_id;
                        $this->db->table('product_attribute')
                            ->insert($productAttributes);
                    }
                } else {
                    $this->db->table('product_attribute')
                        ->where('product_id', $productId)
                        ->where('position', $position)
                        ->delete();
                }
                unset($values[$key]);
                unset($values['attrv' . $position]);
                $position++;
            }
        }
        return $values;
    }

    public
    function updateName($id, $name)
    {
        $this->db->table('attribute_lang')->where('attribute_id', $id)->update(['name' => $name]);
    }

    public
    function getVariantsCount($variantId)
    {
        $count = $this->db->table('product_attribute')
            ->where('product_id', $variantId)
            ->max('position');
        return $count;
    }

    public
    function getTypes()
    {
        return $this->attributeTypes;
    }

    public
    function save($values, $attributeId)
    {
        $locales = $this->localeRepository->getAll();
        $locValues = [];
        foreach ($locales as $locale) {
            $locValues[$locale->id] = $values['locale' . $locale->id];
            unset($values['locale' . $locale->id]);
        }
        if ($attributeId) {
            $this->db->table($this->table)->where('id', $attributeId)->update($values);
            foreach ($locValues as $key => $locValue) {
                $this->db->table('attribute_lang')
                    ->where('attribute_id', $attributeId)
                    ->where('locale_id', $key)
                    ->update(['name' => $locValue]);
            }
        } else {
            $newId = $this->db->table($this->table)->insert($values);
            foreach ($locValues as $key => $locValue) {
                $this->db->table('attribute_lang')
                    ->insert([
                        'name' => $locValue,
                        'locale_id' => $key,
                        'attribute_id' => $newId
                    ]);
            }
        }
    }

    public
    function getValue($productId, $position)
    {
        $productAttribute = $this->db->table('product_attribute')
            ->where('product_id', $productId)
            ->where('position', $position)
            ->fetch();
        if (!$productAttribute) {
            return '';
        }
        $attributeValue = $this->db->table('attribute_value_lang')->where('attribute_value_id', $productAttribute->attribute_value_id)->fetch();
        return $attributeValue ? $attributeValue->value : '';
    }

    public
    function getAttributeName($productId, $position)
    {
        $child = $this->db->table('product')->where('parent_id', $productId)->limit(1)->fetch();
        if (!$child) {
            return '';
        }
        $productAttribute = $this->db->table('product_attribute')
            ->where('product_id', $child->id)
            ->where('position', $position)
            ->fetch();
        if (!$productAttribute) {
            return '';
        }
        $attributeName = $this->db->table('attribute_lang')->where('attribute_id', $productAttribute->attribute_id)->fetch();
        return $attributeName ? $attributeName->name : '';
    }
}