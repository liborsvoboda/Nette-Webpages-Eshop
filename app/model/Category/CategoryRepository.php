<?php


namespace App\Model\Category;


use App\Model\BaseRepository;
use App\Model\LocaleRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\UploadService;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class CategoryRepository extends BaseRepository
{
    private $table = 'category';

    private $recursive = [];

    private $subIds, $appSettingsService, $userLevelRepository, $localeRepository;

    protected $db;

    public function __construct(Context $db,
                                AppSettingsService $appSettingsService,
                                UserLevelRepository $userLevelRepository,
                                LocaleRepository $localeRepository)
    {
        $this->appSettingsService = $appSettingsService;
        $this->db = $db;
        $this->userLevelRepository = $userLevelRepository;
        $this->localeRepository = $localeRepository;
    }

    public function getAll($langId = null): Selection
    {
        $langId = $langId ?? $this->langId();
        $out = $this->db->table($this->table)
            ->where(':category_lang.lang_id', $langId);
        return $out;
    }

    public function getVisible($langId = null)
    {
        $out = $this->getAll($langId)
            ->where('visible', true);
        return $out;
    }

    public function search($string, $limit = 10, $langId = null)
    {
        $out = $this->getAll($langId)
            ->select(':category_lang.*, category.*')
            ->where(':category_lang.name LIKE', '%' . $string . '%')
            ->limit($limit);
        return $out;
    }


    public function getAllToFilter($parent, $langId = null)
    {
        $langId = $langId ?? $this->langId();
        $categories = [];
        $cats = $this->db->table($this->table)
            ->select(':category_lang.*, category.*')
            ->where(':category_lang.lang_id', $langId)
            ->where('parent_id', $parent);
        foreach ($cats as $cat) {
            $categories['cat-' . $cat->id] = $cat->name;
        }
        return $categories;
    }

    public function getAllToSelect($langId = null)
    {
        $out = $this->getAll($langId)->order(':category_lang.name')->fetchAll();
        $category = [];
        foreach ($out as $item) {
            $category[$item->id] = $item->related('category_lang')->fetch()->name;
        }
        return $category;
    }

    public function getChildren($parentId = null, $onlyVisible = false, $langId = null): Selection
    {
        $langId = $langId ?? $this->langId();
        $out = $this->db->table($this->table)
            ->select(':category_lang.*, category.*')
            ->where('category.parent_id', $parentId)
            ->where(':category_lang.lang_id', $langId);
        if ($onlyVisible) {
            $out->where('category.visible', $onlyVisible);
        }
        return $out;
    }

    public function getParents($categoryId, $onlyVisible = true, $langId = null)
    {
        $parents = [];
        $c = $this->getById($categoryId, $langId);
        if (!$c) {
            return $parents;
        }
        $cat = clone $this->getById($categoryId, $langId);
        while ($cat->parent_id !== null) {
            $cat = $this->getById($cat->parent_id, $langId);
            $parents[] = $cat;
        }
        krsort($parents);
        return $parents;
    }

    public function get($categoryId, $langId = null)
    {
        $langId = $langId ?? $this->langId();
        $out = $this->db->table($this->table)
            ->where('category.id', $categoryId)
            ->where(':category_lang.lang_id', $langId)
            ->fetch();
        return $out;

    }

    public function getIdBySlug($slug)
    {
        $out = $this->getAll()
            ->where(':category_lang.slug', $slug)->fetch();
        return $out ? $out->id : null;
    }

    public function getSlugById($id)
    {
        $out = $this->getAll()
            ->select(':category_lang.*')
            ->where(':category_lang.category_id', $id)->fetch();
        return $out ? $out->slug : null;
    }

    public function getIdByName($name, $langId = null)
    {
        $langId = $langId ?? $this->langId();
        $out = $this->getAll($langId)
            ->where(':category_lang.name', $name)->fetch();
        return $out ? $out->id : null;
    }

    public function getFullPathToSelect($langId = null)
    {
        $cats = $this->getAll($langId);
        $path = [];
        foreach ($cats as $cat) {
            $this->recursive = [];
            $path[$cat->id] = implode(' > ', array_reverse($this->getRecursiveNames($cat->id, $langId)));
        }
        return $path;
    }

    private function getRecursiveNames($categoryId, $langId = null)
    {
        $cat = $this->getAll($langId)
            ->select(':category_lang.*, category.*')
            ->where('category.id', $categoryId)->fetch();
        if ($cat->parent_id === null) {
            $this->recursive[] = $cat->name;
            return $this->recursive;
        } else {
            $this->recursive[] = $cat->name;
            return $this->getRecursiveNames($cat->parent_id);
        }
    }

    public function getAllToSelectAsBreadCrumbs()
    {
        return $this->getCategoryTree(null);
    }

    public function getNameById($categoryId, $langId = null)
    {
        $langId = $langId ?? $this->langId();
        $out = $this->db->table($this->table)
            ->where('category.id', $categoryId)
            ->where(':category_lang.lang_id', $langId)
            ->fetch();
        return $out->related('category_lang')->fetch()->name;
    }

    public function getById($categoryId, $langId = null)
    {
        $langId = $langId ?? $this->langId();
        $out = $this->getAll($langId)
            ->select(':category_lang.*, category.*')
            ->where('category.id', $categoryId)
            ->where(':category_lang.lang_id', $langId)
            ->fetch();
        return $out;
    }

    public function getCategoryTree($parentId)
    {
        $categories = array();
        $result = $this->getChildren($parentId, true);
        foreach ($result as $mainCategory) {
            $category = array();
            $category['id'] = $mainCategory->id;
            $category['name'] = $mainCategory->name;
            $category['slug'] = $mainCategory->slug;
            $category['parent_id'] = $mainCategory->parent_id;
            $category['sub_categories'] = $this->getCategoryTree($category['id']);
            $categories[$mainCategory->id] = $category;
        }
        return $categories;
    }

    public function getSubIds($parentId)
    {
        $result = $this->getChildren($parentId)->fetchAll();
        if (!$result) {
            return $parentId;
        }
        foreach ($result as $mainCategory) {
            $category = array();
            $this->subIds = $this->subIds . $mainCategory->id . ',';
            $category['name'] = $mainCategory->name;
            $category['parent_id'] = $mainCategory->parent_id;
            $category['sub_categories'] = $this->getSubIds($mainCategory->id);
        }
        return substr($this->subIds, 0, -1);
    }


    public function getMainMenu()
    {
        $out = [];
        $level1 = $this->getChildren(null, true);
        foreach ($level1 as $l1) {
            $out[$l1->id] = [
                'id' => $l1->id,
                'name' => $l1->name,
                'slug' => $l1->slug,
                'image' => $l1->image
            ];
            $level2 = $this->getChildren($l1->id, true);
            foreach ($level2 as $l2) {
                $out[$l1->id]['children'][$l2->id] = [
                    'name' => $l2->name,
                    'slug' => $l2->slug,
                    'image' => $l2->image
                ];
                $level3 = $this->getChildren($l2->id, true);
                foreach ($level3 as $l3) {
                    $out[$l1->id]['children'][$l2->id]['children'][$l3->id] = [
                        'name' => $l3->name,
                        'slug' => $l3->slug,
                    ];
                }
            }
        }
        return $out;
    }

    public function slugToId($category)
    {
        $slug = $category;
        $out = $this->getIdBySlug($slug);
        return $out;
    }

    public function idToSlug($categoryId)
    {
        $out = $this->getSlugById($categoryId);
        return $out;
    }


    public function create($name, $parent_id)
    {
        if ($parent_id == 0) {
            $parent_id = null;
        }
        $test = $this->getAll()
            ->where(':category_lang.name', $name)
            ->where('parent_id', $parent_id)->fetch();
        if ($test) {
            return $test->id;
        } else {
            $newCategory = new ArrayHash();
            $newCategory->parent_id = $parent_id;
            $newCategory->visible = 1;
            $newId = $this->db->table('category')->insert($newCategory);
            $newLang = new ArrayHash();
            $newLang->lang_id = $this->langId();
            $newLang->category_id = $newId;
            $newLang->name = $name;
            $newLang->slug = Strings::webalize($name . '-' . $newId);
            $this->db->table('category_lang')->insert($newLang);
            return $newId->id;
        }
    }

    public function add($values)
    {
        $newCategory = [];
        $newCategory['parent_id'] = $values->parent_id;
        if ($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/category/');
            $newCategory['image'] = $values->image;
        }
        $newCategory['heureka_id'] = $values->heureka_id;
        $newCategory['pricemania_id'] = $values->pricemania_id;
        $values->discounts = $this->makeDiscounts($values);
        $newId = $this->db->table($this->table)->insert($newCategory);
        if (strlen($values->slug) < 1) {
            $values->slug = Strings::webalize($values->name . '-' . $newId);
        }
        $this->db->table('category_lang')->insert([
            'lang_id' => $this->langId(),
            'category_id' => $newId,
            'name' => $values->name,
            'slug' => $values->slug,
            'description' => $values->description,
            'description_end' => $values->description_end,
            'seoDescription' => $values->seoDescription,
            'seoTitle' => $values->seoTitle
        ]);
    }

    public function save($values, $categoryId = null)
    {
        $parent_id = $values->parent_id;
        if ($parent_id == 0) {
            $parent_id = null;
        }
        if ($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/category/');
        }
        $update = ['parent_id' => $parent_id, 'visible' => $values->visible];
        if ($values->image) {
            $update['image'] = $values->image;
        }
        if ($values->heureka_id) {
            $update['heureka_id'] = $values->heureka_id;
        }
        if ($values->pricemania_id) {
            $update['pricemania_id'] = $values->pricemania_id;
        }
        if ($values->gtaxonomy_id) {
            $update['gtaxonomy_id'] = $values->gtaxonomy_id;
        }
        $update['discounts'] = $this->makeDiscounts($values);
        $isInsert = (!$categoryId);
        if (!$isInsert) {
            $this->db->table($this->table)->where('id', $categoryId)->update($update);
        } else {
            $categoryId = $this->db->table($this->table)->insert($update);
        }

//        $this->db->table('category_lang')->where('category_id', $categoryId)->delete();
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $slug = $values['locale' . $locale->id]->slug;
            if (!Strings::trim($slug)) $slug = Strings::webalize($values['locale' . $locale->id]->name . '-' . $categoryId);
            //if (!Strings::trim($slug)) $slug = Strings::webalize($values['locale' . $locale->id]->name);
            // $slug = strlen($values['locale' . $locale->id]->slug) < 1 ? Strings::webalize($values['locale' . $locale->id]->name . '-' . $categoryId) : $values['locale' . $locale->id]->slug;
            $categoryLang = [
                'name' => $values['locale' . $locale->id]->name,
                'description' => $values['locale' . $locale->id]->description,
                'description_end' => $values['locale' . $locale->id]->description_end,
                'seoDescription' => $values['locale' . $locale->id]->seoDescription,
                'seoTitle' => $values['locale' . $locale->id]->seoTitle,
               'slug' => $slug
            ];

            if ($isInsert) {
                $categoryLang = array_merge($categoryLang, [
                    'category_id' => $categoryId,
                    'lang_id' => $locale->lang->id
                ]);
                $this->db->table('category_lang')
                    ->insert($categoryLang);
            } else {
                $this->db->table('category_lang')
                    ->where('category_id', $categoryId)
                    ->where('lang_id', $locale->lang->id)
                    ->update($categoryLang);
            }
        }
    }

    public function makeDiscounts($values)
    {
        $discounts = [];
        $userLevels = $this->userLevelRepository->getAll();
        foreach ($userLevels as $userLevel) {
            if (isset($values['usrlvl' . $userLevel->id])) {
                $discounts[$userLevel->id] = $values['usrlvl' . $userLevel->id];
            }
        }
        return json_encode($discounts);
    }

    public function getDiscounts($categoryId)
    {
        $category = $this->getById($categoryId);
        return json_decode($category->discounts, true);
    }

    /**
     * @param int $productId
     * @param array $categories
     */
    public function saveMultiCat(int $productId, array $categories)
    {
        if (count($categories) < 1) {
            $this->db->table('product_category')->where('product_id', $productId)->delete();
        }
        foreach ($categories as $category) {
            $this->db->table('product_category')->insert(['product_id' => $productId, 'category_id' => $category]);
        }
    }

    /**
     * @param int $productId
     * @return array
     */
    public function getMultiCat(int $productId): array
    {
        return $this->db->table('product_category')->where('product_id', $productId)
            ->fetchPairs('category_id', 'category_id');
    }

    /**
     * @param array $categories
     * @return array
     */
    public function getIdsInMultiCat(array $categories): array
    {
        return $this->db->table('product_category')->where('category_id', $categories)->fetchPairs('product_id', 'product_id');
    }

    public function getLangItems($categoryId, $langId)
    {
        return $this->db->table('category_lang')->where('category_id', $categoryId)->where('lang_id', $langId);
    }

    public function oldSlugToId($slug)
    {
        $product = $this->db->table('category_lang')->where('old_url', '/'.$slug.'/')->where('lang_id', $this->langId())->fetch();
        return $product ? $product->category_id : null;
    }

}