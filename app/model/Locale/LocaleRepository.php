<?php


namespace App\Model;


use App\FrontModule\Presenters\BasePresenter;
use Nette\Database\Context;

class LocaleRepository extends BaseRepository
{

    private $db, $table = 'locale', $currencyId;

    public function __construct(Context $db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getToSelect()
    {
        $data = $this->getAll()->fetchAll();
        foreach ($data as $item) {
            $out[$item->id] = $item->country->name;
        }
        return $out;
    }

    public function getLocaleByCountryCode($code)
    {
        return $this->getAll()->where('country.code', $code);
    }

    public function add($values)
    {
        $this->getAll()->insert($values);
    }

    public function getById($id)
    {
        return $this->getAll()->where('id', $id);
    }

    public function getByLangId($langId)
    {
        return $this->getAll()->where('lang_id', $langId);
    }

    public function getIdByLangId($langId)
    {
        $id = $this->getByLangId($langId)->fetch();
        return $id ? $id->id : 0;
    }

    public function getByLang(string $lang)
    {
        return $this->getAll()->select('locale.*, lang.*, currency.*')->where('lang.locale', $lang);
    }

    public function getIdByLang(string $lang)
    {
        $locale = $this->getByLang($lang)->fetch();
        return $locale ? $locale->lang_id : 1;
    }

    public function getIsoByLang(string $lang)
    {
        $iso = $this->getByLang($lang)->fetch();
        return $iso ? $iso->locale->locale : 'sk_SK';
    }

    public function getCurrencyByLang(string $lang)
    {
        $currency = $this->getByLang($lang)->fetch();
        $this->currencyId = $currency ? $currency->currency->id : 1;
        return $currency ? $currency->currency->iso : 'EUR';
    }

    public function getCurrencyIdByLang(string $lang)
    {
        $currency = $this->getByLang($lang)->fetch();
        return $currency->id;
    }

    public function getLangIdByLang($lang)
    {
        $langId = $this->getAll()->select('locale.*, lang.*, currency.*')->where('lang.locale', $lang)->fetch();
        return $laingId ?? $langId->id;
    }

    public function getCurrencyIdByLangId($langId)
    {
        $locale = $this->getAll()->where('lang_id', $langId)->fetch();
        return $locale->currency_id;
    }

    public function getCurrencySymbolByLangId($langId)
    {
        $locale = $this->getAll()->where('lang_id', $langId)->fetch();
        return $locale->currency->symbol;
    }

    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    public function getLangsToSelect()
    {
        $out = [];
        $locales = $this->getAll();
        foreach ($locales as $locale) {
            $out[$locale->lang->id] = $locale->lang->name;
        }
        return $out;
    }

    public function getLangsForHomepage()
    {
        $out = [];
        $locales = $this->getAll();
        foreach ($locales as $locale) {
            $out[$locale->lang->id] = [
                'code' => strtoupper($locale->country->code),
                'url' => $locale->url
            ];
        }
        return $out;
    }

    public function getCountryid($localeString)
    {
        $localeString = str_replace('cs', 'cz', $localeString);
        $locale = $this->db->table('country')->where('code', $localeString)->fetch();
        return $locale ? $locale->id : null;
    }

    public function getLocaleByCurrencyId($currencyId)
    {
        $locale = $this->db->table('locale')->where('currency_id', $currencyId)->fetch();
        return $locale ? $locale->lang->locale : null;
    }

    public function getLocaleIdByCurrencyId($currencyId)
    {
        $locale = $this->db->table('locale')->where('currency_id', $currencyId)->fetch();
        return $locale ? $locale->id : null;
    }

    public function getLocaleByLangId($langId)
    {
        $locale = $this->db->table('locale')->where('lang_id', $langId)->fetch();
        return $locale ? $locale->lang->locale : null;
    }

    public function getCurrencyIsoByLocaleId($localeId)
    {
        $currency = $this->db->table('locale')->where('id', $localeId)->fetch();
        return $currency ? $currency->currency->iso : 'EUR';
    }

    public function getLocaleByLocaleId($localeId)
    {
        $locale = $this->db->table('locale')->where('id', $localeId)->fetch();
        return $locale ? $locale->lang->locale : 'sk';
    }

    public function getCurrencyIdByLocaleId($localeId)
    {
        $currency = $this->db->table('locale')->where('id', $localeId)->fetch();
        return $currency ? $currency->currency->id : 1;
    }

    public function getLangIdByLocaleId($localeId)
    {
        $lang = $this->db->table('locale')->where('id', $localeId)->fetch();
        return $lang ? $lang->lang->id : 1;
    }

    public function getCurrentLocale()
    {
        $locale = $this->getLocaleByLangId($this->langId());
        if($locale) {
            $locale = str_replace('cs', 'cz', $locale);
        }
        return $locale;
    }

    public function updateExchangeRate($czkeur)
    {
        $this->getAll()->where('country_id', 2)->update(['exchange_rate' => $czkeur]);
    }

    public function getExchangeRate($localeId)
    {
        $locale = $this->getById($localeId)->fetch();
        return $locale->exchange_rate;
    }
}