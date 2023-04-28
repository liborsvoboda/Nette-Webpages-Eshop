<?php

namespace App\Model\Page;

use App\Model\BaseRepository;
use App\Model\LocaleRepository;
use App\Model\Setting\SettingRepository;
use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Utils\Strings;

class PageRepository extends BaseRepository
{

    private $db, $table = 'page', $settingRepository, $localeRepository;

    public function __construct(Context $db, SettingRepository $settingRepository, LocaleRepository $localeRepository)
    {
        $this->db = $db;
        $this->settingRepository = $settingRepository;
        $this->localeRepository = $localeRepository;
    }

    public function getAll($langId = null)
    {
        $langId = $langId ?? $this->langId();
        return $this->db->table($this->table)
            ->select(':page_lang.*, page.*')
            ->where(':page_lang.lang_id', $langId);
    }

    public function getById($id): Selection
    {
        return $this->getAll()
            ->where('page.id', $id);
    }

    public function getBySlug($slug): Selection
    {
        return $this->getAll()
            ->where(':page_lang.slug', $slug);
    }

    public function save($values, $pageId)
    {
        $active = ['active' => $values->active];
        if ($pageId) {
            $this->db->table($this->table)->where('id', $pageId)->update($active);
        } else {
            $pageId = $this->db->table($this->table)->insert($active);
        }
        $locales = $this->localeRepository->getAll();
        $this->db->table('page_lang')->where('page_id', $pageId)->delete();
        foreach ($locales as $locale) {
            if (strlen($values['locale' . $locale->id]->slug) < 1) {
                $values['locale' . $locale->id]->slug = Strings::webalize($values['locale' . $locale->id]->title);
            }
            $pageLang = [
                'page_id' => $pageId,
                'lang_id' => $locale->lang->id,
                'title' => $values['locale' . $locale->id]->title,
                'text' => $values['locale' . $locale->id]->text,
                'slug' => $values['locale' . $locale->id]->slug,
            ];
            $this->db->table('page_lang')->insert($pageLang);
        }
    }

    public function getIdBySlug($slug)
    {
        $out = $this->getAll()
            ->where(':page_lang.slug', $slug)->fetch();
        return $out ? $out->id : null;
    }

    public function getSlugById($id)
    {
        $out = $this->getAll()
            ->select(':page_lang.*')
            ->where(':page_lang.page_id', $id)->fetch();
        return $out ? $out->slug : null;
    }

    public function slugToId($page)
    {
        $slug = $page;
        $out = $this->getIdBySlug($slug);
        return $out;
    }

    public function idToSlug($pageId)
    {
        $out = $this->getSlugById($pageId);
        return $out;
    }

    public function saveFloatText($floatText)
    {
        $this->settingRepository->setValue('floatText', $floatText);
    }

    public function getFloatText()
    {
        return $this->settingRepository->getValue('floatText');
    }

    public function saveMainMetaDescription($mainMetaDescription)
    {
        $this->settingRepository->setValue('mainMetaDescription', $mainMetaDescription);
    }

    public function getMainMetDescription(string $locale = 'sk')
    {
        $key = 'mainMetaDescription';
        if ($locale != 'sk') $key .= '_' . $locale;
        return $this->settingRepository->getValue($key);
    }

    public function getLangItems($pageId, $langId)
    {
        return $this->getAll($langId)->where('page.id', $pageId);
    }

}
