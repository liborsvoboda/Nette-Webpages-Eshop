<?php


namespace App\Model;


use Nette\Database\Context;

class BaseRepository
{
    protected static $lang = 1, $locale = 'sk';

    public static function setLang($langId)
    {
        self::$lang = $langId;
    }

    protected function langId()
    {
        return self::$lang;
    }

    public static function getLang() {
        return self::$lang;
    }

    public function localeId()
    {
        return 1;
    }

    public static function setLocale($locale)
    {
        self::$locale = $locale;
    }

    public function locale()
    {
        return self::$locale;
    }

    public static function getLocale()
    {
        return self::$locale;
    }
}