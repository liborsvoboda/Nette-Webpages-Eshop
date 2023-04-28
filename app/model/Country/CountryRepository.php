<?php


namespace App\Model\Country;


use App\Model\BaseRepository;
use Nette\Database\Context;

class CountryRepository extends BaseRepository
{
    private $db, $table = 'country';
    
     public function __construct(Context $db)
    {
        $this->db = $db;
    }
    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getNameById($id)
    {
        $country = $this->getAll()->where('id', $id)->fetch();
        return $country ? $country->name : '';
    }

    public function getCodeById($id)
    {
        $country = $this->getAll()->where('id', $id)->fetch();
        return $country ? $country->code : '';
    }

    public function getForSelect(string $limitLocale = null, string $key = 'id', string $limitKey = 'code')
    {
        $country = $this->getAll();
        if ($limitLocale) {
            //if ($limitLocale == 'cs') $limitLocale = 'cz';
            //$country->where($limitKey, $limitLocale);
            $country->where('code',['sk','cz']);
        }
        $out = $country->fetchPairs($key,'name');
        return $out;
    }

    public function getIdByCode($code)
    {
        return $this->getAll()->where('code', $code);
    }
}