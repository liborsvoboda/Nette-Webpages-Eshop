<?php


namespace App\Model\Product;


use App\Model\Attribute\AttributeRepository;
use App\Model\BaseRepository;
use App\Model\Category\CategoryRepository;
use App\Model\LocaleRepository;
use App\Model\Profit\ProfitRepository;
use App\Model\Services\AppSettingsService;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Database\Table\ActiveRow;
use Nette\Http\Session;
use App\Model\Services\UploadService;
use Nette\Utils\Strings;
use Tracy\Debugger;

class ProductRepository extends BaseRepository
{

    /**
     * @var array
     */
    private $productToSlugMap;

    /**
     * @var array
     */
    private $slugToProductMap;

    private $sortingDB = [
        1 => 'product.id DESC',
        2 => 'price DESC',
        3 => 'price ASC',
        4 => 'name ASC',
        5 => 'name DESC'
    ];

    const SORTING = [
        // 1 => 'Najnovšie',
        // 2 => 'Najvyššia cena',
        // 3 => 'Najnižšia cena',
        // 4 => 'A-Z vzostupne',
        // 5 => 'Z-A zostupne'
        1 => 'products.sort.newest',
        2 => 'products.sort.most_expensive',
        3 => 'products.sort.cheapest',
        4 => 'products.sort.name_asc',
        5 => 'products.sort.name_desc'
    ];

    const NULL_IMG = 'assets/front/img/no-image.png';

    const BASE_DPH = 20;

    const REVIEW_HIDDEN = 0,
        REVIEW_VISIBLE = 1,
        REVIEW_PENDING = 2;

    const STOCK_SETTINGS = [
        'skladom (u Vás do 24-48hod.)',
        'na objednávku (u Vás do 4-7 dní)',
        'na objednávku (u Vás do 14-28 dní)',
        'nie je skladom',
    ];


    private $table = 'product',
        $sorting = null,
        $priceFrom = null,
        $priceTo = null,
        $producers = null,
        $session,
        $attributes = [],
        $onlyStock = null,
        $appSettingsService,
        $localeRepository,
        $userLevelRepository;

    protected $db,
        $attributeRepository,
        $profitRepository,
        $categoryRepository;

    public function __construct(
        Context             $db,
        AttributeRepository $attributeRepository,
        Session             $session,
        ProfitRepository    $profitRepository,
        AppSettingsService  $appSettingsService,
        CategoryRepository  $categoryRepository,
        UserLevelRepository $userLevelRepository,
        LocaleRepository    $localeRepository
    )
    {
        $this->db = $db;
        $this->attributeRepository = $attributeRepository;
        $this->profitRepository = $profitRepository;
        $this->appSettingsService = $appSettingsService;
        $this->categoryRepository = $categoryRepository;
        $this->session = $session;
        $this->userLevelRepository = $userLevelRepository;
        $this->localeRepository = $localeRepository;
    }

    public function getAll($langId = null): Selection
    {
        $langId = $langId ?? $this->langId();
        $out = $this->db->table('product')
            ->select(':product_lang.*,:product_price.price,:product_price.price_vat')
            ->select('product.*, product.id AS productId, product.id AS id')
            ->where(':product_lang.lang_id', $langId);
        return $out;
    }

    public function getActive($langId = null)
    {
        $currencyId = $this->localeRepository->getCurrencyIdByLangId($this->langId());
        $products = $this->getAll($langId)->where('active', 1)
            ->where(':product_price.price_vat > 0')
            ->where(':product_price.locale_id', $currencyId)
            ->order('product.sort ASC');
        return $products;
    }

    public function getForSelect()
    {
        $products = $this->getActive();
        $out = $products->fetchPairs('id', 'name');
        return $out;
    }

    public function getForHomepage($limit = 10)
    {
        return $this->withImage($this->getFiltered()->limit($limit));
    }

    public function getSimilar($categoryId, $productId, $limit = 4)
    {
        return $this->withImage($this->getActive())->where('category_id', $categoryId)->where('product.id != ?', $productId)->limit($limit)->order('product.id DESC');
    }

    public function getFiltered()
    {
        $out = $this->getActive()
            ->where(':product_price.price_vat > 0');
        if ($this->sorting) {
            $out->order($this->sorting);
        }
        return $out;
    }

    public function getIdByEan($ean)
    {
        $id = $this->getByEan($ean)->fetch();
        return $id ? $id->id : null;
    }

    public function getIdBySku($sku)
    {
        $id = $this->getBySku($sku)->fetch();
        return $id ? $id->product_id : null;
    }

    public function getBySku($sku)
    {
        return $this->getAll()->where('sku', $sku);
    }


    public function getByTag($tag)
    {
        return $this->getAll()->where('tag', $tag);
    }

    public function search($string, $limit = 10, $langId = null)
    {
        $out = $this->withImage($this->getActive($langId))
            ->where(':product_lang.name LIKE ? OR product.ean LIKE ? OR product.sku LIKE ?', '%' . $string . '%', '%' . $string . '%', '%' . $string . '%')
            ->where(':product_price.price_vat > ?', 0)
            ->limit($limit);
        return $out;
    }

    public function getSearched(string $string)
    {
        $out = $this->getAll()
            ->where(':product_lang.name LIKE ? OR product.ean LIKE ? OR product.sku LIKE ?', '%' . $string . '%', '%' . $string . '%', '%' . $string . '%');
        if ($this->sorting) {
            $out->order($this->sorting);
        }
        $out->group('product.id');
        return $out;
    }

    public function setSorting($sorting)
    {
        $this->sorting = $this->sortingDB[$sorting];
    }

    public function getAllLang()
    {
        return $this->db->table('product_lang');
    }

    public function getByCategory($categoryId)
    {
        return $this->getAll()
            ->where('product.category_id', $categoryId);
    }

    public function getById($id, $langId = null): Selection
    {
        $langId = $langId ?? $this->langId();
        $out = $this->getAll($langId)
            ->where('product.id', $id);
        return $out;
    }

    public function getNextProduct(ActiveRow $product)
    {
        return $this->getAll()->where('category_id', $product->category_id)->where('product.id > ?', $product->id)->limit(1)->order('product.id ASC')->fetch();
    }

    public function getPreviousProduct(ActiveRow $product)
    {
        return $this->getActive()->where('category_id', $product->category_id)->where('product.id < ?', $product->id)->limit(1)->order('product.id ASC')->fetch();
    }

    public function getIdBySlug($slug)
    {
        $out = $this->getActive()
            ->where(':product_lang.slug', $slug)
            ->where('product.priceVat > ?', 0)
            ->where('image IS NOT NULL')
            ->fetch();
        return $out ? $out->productId : null;
    }

    public function slugToId($slug)
    {
        $product = $this->db->table('product_lang')->where('slug', $slug)->where('lang_id', $this->langId())->fetch();
        if ($product) {
            $test = $this->getActive($this->langId())->where('product.id', $product->product_id)->fetch();
            if (!$test) {
                return null;
            }
        }
        return $product ? $product->product_id : null;
    }

    public function oldSlugToId($slug)
    {
        $product = $this->db->table('product_lang')->where('old_url', 'produkt/' . $slug . '/')->where('lang_id', $this->langId())->fetch();
        return $product ? $product->product_id : null;
    }

    public function idToSlug($id)
    {
        $product = $this->db->table('product_lang')->where('product_id', $id)->where('lang_id', $this->langId())->fetch();
        return $product ? $product->slug : null;
    }

    public function getInCategories($categories, $filter = true)
    {
        if (!is_array($categories)) {
            $categories = explode(',', $categories);
        }
        $multi = $this->categoryRepository->getIdsInMultiCat($categories);
        $products = $this->withImage($this->getActive($this->langId()))->group('product.id');
        if (count($multi) > 0) {
            $products->whereOr(['product.category_id' => $categories, 'product.id' => $multi]);
        } else {
            $products->where('product.category_id', $categories);
        }
        if ($filter === false) {
            return $products;
        }
        if ($this->priceFrom) {
            $products->where(':product_price.price_vat >= ?', $this->priceFrom);
        }
        if ($this->priceTo) {
            $products->where(':product_price.price_vat <= ?', $this->priceTo);
        }
        if ($this->onlyStock) {
            $products->where('product.onStock > ?', 0);
        }

        if ($this->producers) {
            $products->where('product.producer_id', $this->producers);
        }
        if (count($this->attributes) > 0) {
            $ids = [];
            foreach ($this->attributes as $attributeKey => $attributeValue) {
                if ($attributeValue === null) {
                    continue;
                }
                $ids[] = $this->db->table('product_attribute')
                    ->where('attribute_id = ?', $attributeKey)
                    ->where('attribute_value_id', $attributeValue)
                    ->fetchPairs('product_id', 'product_id');
            }
            if (count($ids) == 2) {
                $result = array_intersect($ids[0], $ids[1]);
            } elseif (count($ids) > 2) {
                $result = array_intersect($ids[0], $ids[1]);
                for ($x = 2; $x <= count($ids); $x++) {
                    $result = array_intersect($result, $ids[$x]);
                }
            } else {
                $result = (count($ids) > 0) ? $ids[0] : [];
            }
            if (count($ids) > 0) {
                $products->where('product.id', $result);
            }

        }
        if ($this->sorting) {
            $products->order($this->sorting);
        }
        return $products;
    }

    public function getProducersInCategoriesl($categories)
    {
        if (!is_array($categories)) {
            $categories = explode(',', $categories);
        }
        $products = $this->getAll()
            ->where('product.category_id', $categories);
        $producers = $products->fetchPairs('producer_id', ':product.producer.name');
        return $producers;
    }

    public function getProducersInCategories($categories)
    {
        if (!is_array($categories)) {
            $categories = explode(',', $categories);
        }
        $out = $this->db->table('product')
            ->select('producer_id')
            ->where(':product_lang.lang_id', $this->langId())
            ->where('product.category_id', $categories)
            ->group('producer_id')
            ->fetchPairs('producer_id', 'producer_id');
        return $out;
    }

    public function duplicateProduct($id)
    {
        //table product
        $product = $this->db->table('product')->where('id', $id)->fetch()->toArray();
        unset($product['id']);
        $this->db->table('product')->insert($product);

        $newId = $this->db->table('product')->max("id");

        //table product_attachment
        $product = $this->db->table('product_attachment')->where('product_id', $id)->fetch();
        if ($product != "") {
            $product = $product->toArray();
            $product['product_id'] = $newId;
            unset($product['id']);
            $this->db->table('product_attachment')->insert($product);
        }

        //table product_attribute
        $products = $this->db->table('product_attribute')->where('product_id', $id);
        foreach ($products as $product) {
            $productData = $product;
            $productData = $productData->toArray();
            $productData['product_id'] = $newId;
            unset($productData['id']);
            $this->db->table('product_attribute')->insert($productData);
        }
        //table product_combo
        $products = $this->db->table('product_combo')->where('product_id', $id);
        foreach ($products as $product) {
            $productData = $product;
            $productData = $productData->toArray();
            $productData['product_id'] = $newId;
            unset($productData['id']);
            $this->db->table('product_combo')->insert($productData);
        }
        //table product_gallery
        $products = $this->db->table('product_gallery')->where('product_id', $id);
        foreach ($products as $product) {
            $productData = $product;
            $productData = $productData->toArray();
            $productData['product_id'] = $newId;
            unset($productData['id']);
            $this->db->table('product_gallery')->insert($productData);
        }
        //table product_lang
        $products = $this->db->table('product_lang')->where('product_id', $id);
        foreach ($products as $product) {
            $productData = $product;
            $productData = $productData->toArray();
            $productData['product_id'] = $newId;
            unset($productData['id'], $productData['slug']);
            $this->db->table('product_lang')->insert($productData);
        }
        //table product_price
        $products = $this->db->table('product_price')->where('product_id', $id);
        foreach ($products as $product) {
            $productData = $product;
            $productData = $productData->toArray();
            $productData['product_id'] = $newId;
            unset($productData['id']);
            $this->db->table('product_price')->insert($productData);
        }

    }

    public function getMinPrice($categories)
    {
        $products = $this->getInCategories($categories, false)->fetchAll();
        $min = $this->db->table('product_price')->where('product_id', $products)->where('locale_id', $this->langId())->min('price_vat');
        return $min;
    }

    public function getMaxPrice($categories)
    {
        $products = $this->getInCategories($categories, false)->fetchAll();
        $max = $this->db->table('product_price')->where('product_id', $products)->where('locale_id', $this->langId())->max('price_vat');
        return $max;
    }

    public function setPriceFrom($price)
    {
        $this->priceFrom = $price;
    }

    public function setPriceTo($price)
    {
        $this->priceTo = $price;
    }

    public function setProducers($producers)
    {
        if ($producers) {
            $this->producers = explode(',', $producers);
        }
    }

    public function updateStockCount($productId, $stockCount)
    {
        $this->db->table($this->table)->where('id', $productId)->update(['inStock' => $stockCount]);
    }

    public function add($values)
    {
        Debugger::log($values->image);
        if ($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/product/');
        }
        if ($values->image === null) {
            unset($values->image);
        }
        Debugger::log($values);
        $productValues = [
            'sku' => $values->sku ?? null,
            'producer_id' => $values->producer_id,
            'ean' => $values->ean,
            'commission' => $values->commission,
            'category_id' => $values->category_id,
            'image' => $values->image,
            'unit' => $values->unit,
            'active' => $values->active,
            'featured' => $values->featured,
            'order_min' => $values->order_min,
            'order_max' => $values->order_max,
            'new_tag' => $values->new_tag,
            'is_combo' => $values->is_combo,
            'tip_tag' => $values->tip_tag
        ];
        $product = $this->db->table($this->table)->insert($productValues);
        $values->image = null;
        $this->update($values, $product->id);
        return $product;
    }

    public function update($values, $productId)
    {
        if ($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/product/');
        }
        if ($values->image === null) {
            unset($values->image);
        }
        if (isset($values->multiCat)) {
            $this->categoryRepository->saveMultiCat($productId, $values->multiCat);
            unset($values->multiCat);
        }
        $values = $this->updateProductLanguage($values, $productId);
        $values = $this->updatePrices($values, $productId);
        $values = $this->updateAttributes($values, $productId);
        unset($values->originalPrice);
        $this->db->table($this->table)->where('id', $productId)->update($values);
    }

    private function updateAttributes($values, $productId)
    {
        $attributes = $this->attributeRepository->getAllToSelect();
        foreach ($attributes as $key => $attribute) {
            $this->db->table('product_attribute')->where('product_id', $productId)->where('attribute_id', $key)->delete();
            foreach ($values['attr' . $key] as $value) {
                $this->db->table('product_attribute')->insert([
                    'product_id' => $productId,
                    'attribute_id' => $key,
                    'attribute_value_id' => $value
                ]);
            }
            unset($values['attr' . $key]);
        }
        return $values;
    }

    public function updatePrice($values, $productId)
    {
        $this->db->table($this->table)->where('id', $productId)->update($values);
    }

    private function updateProductLanguage($values, $productId)
    {
        $locales = $this->localeRepository->getAll();
        $userLevels = $this->userLevelRepository->getAll()->fetchAll();
        $userLevelsHorizontal = array_reverse($userLevels);
        foreach ($locales as $locale) {
            $langId = $locale->lang->id;
            $commissions = [];
            foreach ($userLevels as $vertical) {
                $commissions[$vertical->id] = [];
                foreach ($userLevels as $horizontal) {
                    if(isset($values['locale' . $locale->id]['commissions']['commission' . $vertical.'h'.$horizontal])) {
                        $commissions[$vertical->id][$horizontal->id] = $values['locale' . $locale->id]['commissions']['commission' . $vertical.'h'.$horizontal];
                        unset($values['locale' . $locale->id]['commissions']['commission' . $vertical.'h'.$horizontal]);
                    }
                }
            }
            $langValues = [
                'name' => $values['locale' . $locale->id]->name,
                'description' => $values['locale' . $locale->id]->description,
                'description2' => $values['locale' . $locale->id]->description2,
                'sale_text' => $values['locale' . $locale->id]->sale_text,
                'below_button' => $values['locale' . $locale->id]->below_button,
                'perex' => $values['locale' . $locale->id]->perex,
                'ingredients' => $values['locale' . $locale->id]->ingredients,
                'nutritional' => $values['locale' . $locale->id]->nutritional,
                'warnings' => $values['locale' . $locale->id]->warnings,
                'faq' => $values['locale' . $locale->id]->faq,
                'taking' => $values['locale' . $locale->id]->taking,
                'commissions' => json_encode($commissions),
                'benefits' => $values['locale' . $locale->id]->benefits,
            ];
            if (strlen($values['locale' . $locale->id]->slug) < 1) {
                $slug = Strings::webalize($values['locale' . $locale->id]->name . '-' . $productId);
            } else {
                $slug = Strings::webalize($values['locale' . $locale->id]->slug);
            }
            $langValues['slug'] = $slug;
            unset($values['locale' . $locale->id]->name, $values['locale' . $locale->id]->description, $values['locale' . $locale->id]->slug);
            $test = $this->db->table('product_lang')->where('product_id', $productId)->where('lang_id', $langId)->fetch();
            if ($test) {
                $this->db->table('product_lang')->where('product_id', $productId)->where('lang_id', $langId)->update($langValues);
            } else {
                $langValues['product_id'] = $productId;
                $langValues['lang_id'] = $langId;
                $this->db->table('product_lang')->insert($langValues);
            }

        }
        return $values;
    }

    private function updatePrices($values, $productId)
    {
        $locales = $this->localeRepository->getAll();
        $userLevels = $this->userLevelRepository->getAllGroups();

        foreach ($locales as $locale) {
            foreach ($userLevels as $userLevel) {
                $priceVat = $values['locale' . $locale->id]['price' . $userLevel->id];
                $originalPrice = $values['locale' . $locale->id]['orig_price_vat'];

                $vat = $values['locale' . $locale->id]['vat' . $locale->id];
                $price = $priceVat /((100 + $vat) / 100);
                $prices = [
                    'price_vat' => $priceVat,
                    'vat' => $vat,
                    'price' => $price,
                    'orig_price_vat' => $originalPrice,
                ];
                $test = $this->db->table('product_price')
                    ->where('product_id', $productId)
                    ->where('locale_id', $locale->id)
                    ->where('user_group_id', $userLevel->id)
                    ->fetch();
                if (!$test) {
                    $prices['product_id'] = $productId;
                    $prices['locale_id'] = $locale->id;
                    $prices['user_group_id'] = $userLevel->id;
                    $this->db->table('product_price')->insert($prices);
                } else {
                    $this->db->table('product_price')
                        ->where('product_id', $productId)
                        ->where('locale_id', $locale->id)
                        ->where('user_group_id', $userLevel->id)
                        ->update($prices);
                }
            }
            unset($values['locale' . $locale->id]);
        }
        return $values;
    }

    public function updatePricesArray($values, $productIds)
    {
        $values = str_replace(",", ".", $values);
        foreach ($productIds as $productId) {
            $locales = $this->localeRepository->getAll();
            foreach ($locales as $locale) {
                $currencyId = $locale->currency->id;
                $test = $this->db->table('product_price')
                    ->where('product_id', $productId)
                    ->where('currency_id', $currencyId)->fetch();

                $vatValue = (floatval($test->vat) + 100.00) / 100.00;
                $prices = [
                    'price' => (float)$values,
                    'price_vat' => (float)$values * $vatValue,

                ];
                if (!$test) {
                    $prices['product_id'] = $productId;
                    $prices['currency_id'] = $currencyId;
                    $this->db->table('product_price')
                        ->insert($prices);
                } else {
                    $this->db->table('product_price')
                        ->where('product_id', $productId)
                        ->where('currency_id', $currencyId)
                        ->update($prices);
                }
            }
        }
    }

    public function updateBasePricesArray($values, $productIds)
    {
        $values = str_replace(",", ".", $values);
        foreach ($productIds as $productId) {
            $locales = $this->localeRepository->getAll();
            foreach ($locales as $locale) {
                $currencyId = $locale->currency->id;
                $test = $this->db->table('product_price')
                    ->where('product_id', $productId)
                    ->where('currency_id', $currencyId)->fetch();

                $vatValue = (floatval($test->vat) + 100.00) / 100.00;
                $prices = [
                    'base_price' => (float)$values,
                    'base_price_vat' => (float)$values * $vatValue,

                ];
                if (!$test) {
                    $prices['product_id'] = $productId;
                    $prices['currency_id'] = $currencyId;
                    $this->db->table('product_price')
                        ->insert($prices);
                } else {
                    $this->db->table('product_price')
                        ->where('product_id', $productId)
                        ->where('currency_id', $currencyId)
                        ->update($prices);
                }
            }
        }
    }

    public function updatePricePercent($ids, float $percent, array $cols = ['price', 'base_price'])
    {
        $prods = $this->getById($ids);
        $locales = $this->localeRepository->getAll();
        $coef = (100 + $percent) / 100;
        foreach ($prods as $prod) {
            $prices = $price = $this->db->table('product_price')->where('product_id', $prod->id);
            foreach ($prices as $price) {
                $args = [];
                foreach ($cols as $col) {
                    $args[$col] = round($price->{$col} * $coef, 2);
                    $args[$col . '_vat'] = round($price->{$col . '_vat'} * $coef, 2);
                }

                $this->db->table('product_price')
                    ->where('id', $price->id)
                    ->update($args);
            }
        }
    }

    public function addProductLanguage($values)
    {
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $langId = $locale->lang->id;
            $langValues = [
                'name' => $values['locale' . $locale->id]->name,
                'description' => $values['locale' . $locale->id]->description,
                'perex' => $values['locale' . $locale->id] ?? null,
                'lang_id' => $langId,
                'slug' => strlen($values['locale' . $locale->id]->slug) > 0 ? $values['locale' . $locale->id]->slug : Strings::webalize($values['locale' . $locale->id]->name) . '-' . $newId,
                'product_id' => $newId
            ];
        }

        $this->db->table('product_lang')->insert($langValues);
    }

    public function addProductPrices($values)
    {

        $locales = $this->localeRepository->getAll();

        foreach ($locales as $locale) {
            $langId = $locale->lang->id;

            $priceVat = ($values->price_vat) ? $values->price_vat : round($values->price * ((100 + $values->vat) / 100), 2);
            $price = ($values->price) ? $values->price : round($priceVat / ((100 + $values->vat) / 100), 2);
            $origPriceVat = round($values->origPrice * ((100 + $values->vat) / 100), 2);

        }

        $this->db->table('product_lang')->insert($langValues);
    }

    public function addFavorite($productId)
    {
        $added = false;
        $favorites = [];
        $section = $this->session->getSection('favorite');
        if (isset($section->favorites)) {
            $favorites = $section->favorites;
            if (isset($favorites[$productId])) {
                unset($favorites[$productId]);
                $section->favorites = $favorites;
            } else {
                $favorites[$productId] = $productId;
                $section->favorites = $favorites;
                $added = true;
            }
        } else {
            $favorites[$productId] = $productId;
            $section->favorites = $favorites;
            $added = true;
        }
        return $added;
    }

    public function getFavorites()
    {
        $favorites = [];
        $section = $this->session->getSection('favorite');
        if (isset($section->favorites)) {
            return $section->favorites;
        }
        return $favorites;
    }

    public function getProductDog($userId)
    {
        return $this->db->table('product_dog')->where('user_id', $userId);
    }

    public function hasProductDog(int $userId, int $productId): bool
    {
        return $this->getProductDog($userId)->where('product_id', $productId)->count() > 0;
    }

    public function addProductDog($productId, $userId, $price)
    {
        $test = $this->db->table('product_dog')->where(['user_id' => $userId, 'product_id' => $productId])->fetch();
        if ($test) {
            $this->db->table('product_dog')->where('id', $test->id)->update(['price' => $price]);
        } else {
            $this->db->table('product_dog')->insert(['user_id' => $userId, 'product_id' => $productId, 'price' => $price]);
        }
    }

    public function removeProductDog($itemId)
    {
        $this->db->table('product_dog')->where('id', $itemId)->delete();
    }

    public function removeProductDogByUserProduct(int $userId, int $productId)
    {
        $this->db->table('product_dog')->where('product_id', $productId)->where('user_id', $userId)->delete();
    }

    public function recalculatePrice()
    {
        $profits = $this->profitRepository->getAll();
        $pt = [];
        foreach ($profits as $profit) {
            $pt[] = [
                'from' => $profit->priceFrom,
                'to' => $profit->priceTo,
                'profit' => $profit->profit
            ];
        }
        $products = $this->db->table($this->table);
        foreach ($products as $product) {
            if ($product->noProfit) {
                continue;
            }
            $profit = 1;
            foreach ($pt as $p) {
                if ($product->origPrice >= $p['from'] && $product->origPrice <= $p['to']) {
                    $profit = ($p['profit'] + 100) / 100;
                    break;
                }
            }
            if ($product->origPrice > 0) {
                $price = round($product->origPrice * $profit, 2);
                $priceVat = round($price * (100 + $product->vat) / 100, 2);
                $this->db->table($this->table)->where('id', $product->id)->update([
                    'price' => $price,
                    'priceVat' => $priceVat
                ]);
            }
        }
    }

    private function withImage(Selection $selection): Selection
    {
        return $selection->where('image IS NOT NULL');
    }

    public function changeActive($id, $value)
    {
        $this->db->table($this->table)->where('id', $id)->update(['active' => $value]);
    }

    public function getAllAttributesToFilter($categories)
    {
        $products = $this->getInCategories($categories, false)->fetchPairs('id', 'id');
        $attributes = $this->attributeRepository->getAttributesToFilter($products);
        return $attributes;
    }

    public function setAttributes($attributes)
    {
        if ($attributes) {
            $this->attributes = $attributes;
        }
    }

    public function setOnlyStock($value)
    {
        $this->onlyStock = $value;
    }

    public function getAttributeNameById($attributeId)
    {
        return $this->attributeRepository->getNameById($attributeId);
    }

    public function getTopSellers($categories, $count = 3)
    {
        $products = $this->getInCategories($categories, false)->fetchAll();
        $topCount = $this->db->table('order_item')
            ->select('product_id AS id, COUNT(*) AS num')
            ->where('product_id', $products)
            ->group('product_id')
            ->order('num DESC')
            ->limit($count);
        if ($topCount->count() > 0) {
            $top = $this->getAll()
                ->where('product.id', $topCount->fetchAll())
                ->limit($count);
        } else {
            $top = $this->getInCategories($categories)
                ->order('RAND()')
                ->limit(3);
        }
        return $top;
    }


    /**
     * @param int $productId
     * @param array $values
     * @return bool|int|ActiveRow
     */
    public function insertProductReview(int $productId, array $values, $status = 2)
    {
        $values['product_id'] = $productId;
        $values['status'] = $status;
        $values['lang_id'] = $this->langId();
        return $this->db->table('product_review')->insert($values);
    }


    /**
     * @param int $reviewId
     * @param array $values
     * @return int|null
     */
    public function updateProductReview(int $reviewId, array $values)
    {
        return $this->db->query("UPDATE product_review SET", $values, 'WHERE id = ?', $reviewId);
    }


    /**
     * @param int $productId
     * @return array|\Nette\Database\Table\IRow[]
     */
    public function getProductReviews(int $productId)
    {
        return $this->db->table('product_review')
            ->select('*')
            ->where('product_id', $productId)
            ->where('status', '1')
            ->order('created_at DESC')
            ->fetchAll();
    }


    /**
     * @return array|\Nette\Database\Table\IRow[]
     */
    public function getAllProductReviews()
    {
        return $this->db->query("SELECT product_review.*, product.name FROM product_review LEFT JOIN product_lang product ON product_review.product_id = product.id AND product.lang_id = ? ORDER BY product_review.status DESC, product_review.created_at DESC", $this->langId())->fetchAll();
    }


    /**
     * @param int $id
     * @param string $status
     * @return \Nette\Database\ResultSet
     */
    public function setReviewStatus(int $id, $status = '0')
    {
        return $this->db->query("UPDATE product_review SET status = ? WHERE id = ?", $status, $id);
    }


    /**
     * @param int $id
     * @return \Nette\Database\IRow|ActiveRow|null
     */
    public function getProductReview(int $id)
    {
        return $this->db->table('product_review')
            ->select('*')
            ->where('id', $id)
            ->fetch();
    }


    /**
     * @return Selection
     */
    public function getLangs(): Selection
    {
        return $this->db->table('lang')->select('id, name')->order('name ASC');
    }


    /**
     * @param $values
     * @return false|string
     */
    public function makeLimits($values)
    {
        $limits = [];

        if (isset($values['order_min'])) {
            if (!$values['order_min']) {
                $values['order_min'] = 0;
            }
            $limits['default']['min'] = $values['order_min'];
        }
        if (isset($values['order_max'])) {
            if (!$values['order_max']) {
                $values['order_max'] = 0;
            }
            $limits['default']['max'] = $values['order_max'];
        }

        $userLevels = $this->userLevelRepository->getAll();
        foreach ($userLevels as $userLevel) {
            if (isset($values['usrlvlMin' . $userLevel->id])) {
                $limits[$userLevel->id]['min'] = $values['usrlvlMin' . $userLevel->id];
            }
            if (isset($values['usrlvlMax' . $userLevel->id])) {
                $limits[$userLevel->id]['max'] = $values['usrlvlMax' . $userLevel->id];
            }
        }
        return json_encode($limits);
    }


    /**
     * @param int $productId
     * @param string $userLevel
     * @return object
     */
    public function getLimits(int $productId, string $userLevel = 'default'): object
    {
        $limits = $this->db->table($this->table)->select('orderLimit')->where('id = ?', $productId)->fetchField();

        if ($limits) {
            $obj = json_decode($limits);
            if (!property_exists($obj, 'default')) {
                $obj['default'] = json_decode('{"min":0,"max":0}');
            }

            if ($userLevel != 'default' && !isset($obj->$userLevel)) {
                $userLevel = 'default';
            }

            $min = $obj->$userLevel->min != '' ? $obj->$userLevel->min : $obj->default->min;
            $max = $obj->$userLevel->max != '' ? $obj->$userLevel->max : $obj->default->max;
            return json_decode('{"min":' . $min . ',"max":' . $max . '}');
        }
        return json_decode('{"min":0,"max":0}');
    }

    public function getLangItems($productId, $langId)
    {
        return $this->db->table('product_lang')->where('product_id', $productId)->where('lang_id', $langId);
    }

    public function getPriceItems($productId, $localeId)
    {
        $prices = $this->db->table('product_price')
            ->where('product_id', $productId)
            ->where('locale_id', $localeId)
            ->fetchPairs('user_group_id', 'price_vat');
        return $prices;
    }

    public function getOriginalPrice($productId, $localeId)
    {
        $prices = $this->db->table('product_price')
            ->where('product_id', $productId)
            ->where('locale_id', $localeId)
            ->fetchPairs('user_group_id', 'orig_price_vat');
        return $prices;
    }

    public function getFeatured($count = 8)
    {
        // return $this->getAll()->where('featured', true)->limit($count);
        $featured = $this->getAll()->where('featured', true);
        if ($count) {
            //$featured->limit($count);
        }
        return $featured;
    }

    public function setFeatured($productId)
    {
        $this->db->table($this->table)->where('id', $productId)->update(['featured' => true]);
    }

    public function unsetFeatured($productId)
    {
        $this->db->table($this->table)->where('id', $productId)->update(['featured' => false]);
    }

    /**
     * @param $values
     * @return false|string
     */
    public function makeDiscounts($values)
    {
        $discounts = [];
        $userLevels = $this->userLevelRepository->getAll();
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            foreach ($userLevels as $userLevel) {
                if (isset($values['locale' . $locale->id]['usrlvl' . $locale->id . 'l' . $userLevel->id])) {
                    $discounts[$locale->id][$userLevel->id] = $values['locale' . $locale->id]['usrlvl' . $locale->id . 'l' . $userLevel->id];
                }
            }
        }
        return json_encode($discounts);
    }

    /**
     * @param $values
     * @return mixed
     */
    public function getLimitValues($values)
    {
        $values['orderLimit'] = $this->makeLimits($values);
        $userLevels = $this->userLevelRepository->getAll();
        foreach ($userLevels as $userLevel) {
            unset($values['usrlvl' . $userLevel->id]);
            unset($values['usrlvlMin' . $userLevel->id]);
            unset($values['usrlvlMax' . $userLevel->id]);
        }
        unset($values['order_min']);
        unset($values['order_max']);

        return $values;
    }

    public function addComboProduct($productId, $values)
    {
        $this->db->table('product_combo')->insert([
            'product_id' => $productId,
            'combo_id' => $values->product_id,
            'amount' => $values->amount
        ]);
    }

    public function removeComboProduct($rowId)
    {
        $this->db->table('product_combo')
            ->where('id', $rowId)
            ->delete();
    }

    public function getComboProducts($productId)
    {
        return $this->db->table('product_combo')->where('product_id', $productId);
    }


}
