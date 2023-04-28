<?php


namespace App\Model\Attribute;


use App\Model\BaseRepository;
use App\Model\LocaleRepository;
use Nette\Database\Context;

class AttributeValueRepository extends BaseRepository
{
    private $table = 'attribute_value', $db, $localeRepository;

    public function __construct(Context $db, LocaleRepository $localeRepository)
    {
        $this->db = $db;
        $this->localeRepository = $localeRepository;
    }

    public function getByAttributeId($attributeId)
    {
        $out = $this->db->table('attribute_value')->where('attribute_id', $attributeId);
        return $out;
    }

    public function getValue($id, $localeId)
    {
        $value = $this->db->table('attribute_value_lang')->where('attribute_value_id', $id)->where('locale_id', $localeId)->fetch();
        return $value ? $value->value : '';
    }

    public function update($id, $values)
    {
        foreach ($values as $key => $value) {
            $localeId = str_replace('locale', '', $key);
            $this->db->table('attribute_value_lang')
                ->where('attribute_value_id', $id)
                ->where('locale_id', $localeId)
                ->update(['value' => $value]);
        }
    }

    public function add($attributeId, $values)
    {
        $newId = $this->db->table($this->table)->insert(['attribute_id' => $attributeId]);
        foreach ($values as $key => $value) {
            $localeId = str_replace('locale', '', $key);
            $this->db->table('attribute_value_lang')
                ->insert([
                    'attribute_value_id' => $newId,
                    'value' => $value,
                    'locale_id' => $localeId
                ]);
        }
    }
}