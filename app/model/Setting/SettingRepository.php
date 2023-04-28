<?php


namespace App\Model\Setting;


use App\Model\BaseRepository;
use Nette\Database\Context;

class SettingRepository extends BaseRepository
{
    private $db, $table = 'setting';


    public function __construct(Context $db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getAllAsPairs()
    {
        return $this->getAll()->fetchPairs('key', 'value');
    }

    public function getValue($key, $test = false)
    {
        $value = $this->getAll()->where('key', $key)->fetch();
        if ($value) {
            return $value->value;
        }
        if (isset($value->id) && $test) {
            return $value->id;
        }
        return null;
    }

    public function setValue($key, $value)
    {
        $test = $this->getValue($key, true);
        if ($test !== null) {
            $this->db->table($this->table)->where('key', $key)->update(['value' => $value]);
        } else {
            $this->db->table($this->table)->insert(['key' => $key, 'value' => $value]);
        }
    }

    public function getFreeDelivery($locale)
    {
        $locale = str_replace('cs', 'cz', $locale);
        return $this->getValue('freeDelivery' . strtoupper($locale));
    }

    public function getFreeDeliveryShow()
    {
        return $this->getValue('freeDeliveryShow');
    }

    public function setFreeDelivery($value)
    {
        $this->setValue('freeDelivery', $value);
    }

    public function saveValues($values)
    {
        foreach ($values as $key => $value) {
            $this->setValue($key, $value);
        }
    }

    public function getDefaultLocale()
    {
        return $this->getValue('defaultLocale');
    }
}