<?php


namespace App\Model\Menu;


use App\Model\BaseRepository;
use App\Model\LocaleRepository;
use Nette\Database\Context;

class MenuRepository extends BaseRepository
{
    private $table = 'menu';

    protected $db, $localeRepository;

    const TOP_MENU = 1, FOOTER_COLUMN_2 = 2, FOOTER_COLUMN_3 = 3;

    const POSITION = [

        self::TOP_MENU => 'Hlavní menu',
        self::FOOTER_COLUMN_2 => 'Patička 1. sloupec',
        self::FOOTER_COLUMN_3 => 'Patička 2. sloupec'
    ];

    public function __construct(Context $db, LocaleRepository $localeRepository)
    {
        $this->db = $db;
        $this->localeRepository = $localeRepository;
    }

    public function getAll($langId = null)
    {
        $langId = $langId ?? $this->langId();
        return $this->db->table($this->table)->select(':menu_lang.*, menu.*')->where(':menu_lang.lang_id', $langId)->order('menu.sort ASC');
    }

    public function getFooterColumn2()
    {
        return $this->getAll()->where('position', self::FOOTER_COLUMN_2)->fetchAll();
    }

    public function getFooterColumn3()
    {
        return $this->getAll()->where('position', self::FOOTER_COLUMN_3)->fetchAll();
    }

    public function getById($menuId)
    {
        return $this->getAll()->where('menu.id', $menuId);
    }

    public function getMainMenu($localeId = null)
    {
        $level1 = $this->getAll($localeId)->where('menu.position', self::TOP_MENU)->where('menu.parent_id', null)->order('menu.id');
        foreach ($level1 as $l1) {
            $children = $this->getAll()->where('menu.position', self::TOP_MENU)->where('menu.parent_id', $l1->id)->order('menu.id');
            $childrenArray = [];
            if ($children) {
                foreach ($children as $child) {
                    $childrenArray[$child->id] = [
                        'name' => $child->title,
                        'slug' => $child->slug,
                    ];
                }
            }
            $out[$l1->id] = [
                'name' => $l1->title,
                'slug' => $l1->slug,
                'children' => $childrenArray,
            ];
        }
        return $out ?? [];
    }

    public function getMenuItems($position = null)
    {
        // $items = $this->db->table($this->table);
        $items = $this->getAll();
        if ($position) {
            $items->where('menu.position', $position);
        }
        $out = [];
        foreach ($items as $item) {
            $out[$item->id] = $item->title;
        }
        return $out;
    }

    public function add($values)
    {
        $menu = [
            'parent_id' => $values->parent_id,
            'position' => $values->position,
            'sort' => $values->sort
        ];
        $newId = $this->db->table($this->table)->insert($menu);
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $menuLang = [
                'lang_id' => $locale->id,
                'menu_id' => $newId,
                'title' => $values['locale' . $locale->id]->title,
                'slug' => $values['locale' . $locale->id]->slug
            ];
            $this->db->table('menu_lang')->insert($menuLang);
        }
    }

    public function update($menuId, $values)
    {
        $menu = [
            'parent_id' => $values->parent_id,
            'position' => $values->position,
            'sort' => $values->sort
        ];
        $this->db->table($this->table)->where('id', $menuId)->update($menu);
        $locales = $this->localeRepository->getAll();
        $this->db->table('menu_lang')->where('menu_id', $menuId)->delete();
        foreach ($locales as $locale) {
            $menuLang = [
                'lang_id' => $locale->id,
                'menu_id' => $menuId,
                'title' => $values['locale' . $locale->id]->title,
                'slug' => $values['locale' . $locale->id]->slug
            ];
            $this->db->table('menu_lang')->insert($menuLang);
        }
    }

    public function remove($menuId)
    {
        $this->db->table('menu_lang')->where('menu_id', $menuId)->delete();
        $this->db->table($this->table)->where('id', $menuId)->delete();
    }

    public function getLangItems($menuId, $langId)
    {
        return $this->getAll($langId)->where('menu.id', $menuId);
    }

}