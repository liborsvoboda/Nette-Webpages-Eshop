<?php

namespace App\Model\Import;

use App\Model\Services\AppSettingsService;
use Nette\Database\Connection;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;

/**
 * Import from old WP Woocommerce DB
 * 
 * ```
 * $wp = new WPImport('<mibronDbName>', '<wpDbName>', '<dbUser>', '<dbPassword>', ...);
 * $wp->import();
 */
class WPImport
{

    const ROOT_CAT = 798;
    const WP_UPLOAD_DIR = 'https://www.veganshop.sk/wp-content/uploads/';
    const LANG_ID = 1;
    const CURRENCY_ID = 1;
    const VAT = 20;
    
    /** @var Connection */
    private $wp;

    /** @var AppSettingsService */
    private $appSettingsService;

    private $dbCats = [];
    private $dbProducers = [];
    private $dbAttributes = [];
    private $dbProducts = [];

    public function __construct(string $mibronDb, string $wpDb, string $dbUser, string $dbPassword, AppSettingsService $appSettingsService)
    {
        $this->db = new Connection("mysql:host=127.0.0.1;dbname=$mibronDb", $dbUser, $dbPassword);
        $this->wp = new Connection("mysql:host=127.0.0.1;dbname=$wpDb", $dbUser, $dbPassword);
        $this->appSettingsService = $appSettingsService;
    }

    public function import()
    {
        $this->dbCats = $this->db->fetchPairs('SELECT `import_id`, `id` FROM `category` WHERE `import_id` IS NOT NULL');
        $this->dbProducers = $this->db->fetchPairs('SELECT `import_id`, `id` FROM `producer` WHERE `import_id` IS NOT NULL');
        $this->dbAttributes = $this->db->fetchPairs('SELECT `import_id`, `id` FROM `attribute` WHERE `import_id` IS NOT NULL');
        $this->dbProducts = $this->db->fetchPairs('SELECT `import_id`, `id` FROM `product` WHERE `import_id` IS NOT NULL');

        $this->importCategories();
        $this->importProducers();
        $this->importAttributes();
        $this->importProducts();
    }

    private function importCategories()
    {
        $cats = $this->fetchCategoryTree(self::ROOT_CAT);
        foreach ($cats as $cat) {
            $this->importCategory($cat);
        }
    }

    private function importProducers()
    {
        $producers = $this->fetchProducers();
        foreach ($producers as $producer) {
            $this->importProducer($producer);
        }
    }

    private function importAttributes()
    {
        $rows = $this->wp->fetchAll("SELECT `attribute_name` AS `slug`, `attribute_label` AS `name` FROM `wp_woocommerce_attribute_taxonomies`");
        foreach ($rows as $row) {
            if (!array_key_exists($row->slug, $this->dbAttributes)) {
                $this->db->query("INSERT INTO `attribute`", [
                    'type' => 1,
                    'filterable' => 0,
                    'searchable' => 0,
                    'import_id' => $row->slug
                ]);
                $id = $this->dbAttributes[$row->slug] = $this->db->getInsertId();
                $this->db->query("INSERT INTO `attribute_lang`", [
                    'lang_id' => self::LANG_ID,
                    'attribute_id' => $id,
                    'name' => $row->name
                ]);
            }
        }
    }

    private function importProduct(ArrayHash $product)
    {
        if (!array_key_exists($product->id, $this->dbProducts)) {
            // Product
            $data = [
                'sku' => $product->sku,
                'producer_id' => ($product->producer && isset($this->dbProducers[$product->producer])) ? $this->dbProducers[$product->producer] : null,
                'category_id' => ($product->cats && isset($this->dbCats[$product->cats[0]])) ? $this->dbCats[$product->cats[0]] : null,
                'image' => ($product->thumb) ? $this->upload($product->thumb, 'product') : null,
                'onStock' => $product->stock,
                'unit' => 'ks',
                'import_id' => $product->id
            ];
            $this->db->query("INSERT INTO `product`", $data);
            $id = $this->dbProducts[$product->id] = $this->db->getInsertId();

            // Language data
            $dataLang = [
                'product_id' => $id,
                'lang_id' => self::LANG_ID,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'perex' => $product->perex
            ];
            $this->db->query("INSERT INTO `product_lang`", $dataLang);

            // Price
            $dataPrice = [
                'product_id' => $id,
                'currency_id' => self::CURRENCY_ID,
                'price' => $product->price,
                'price_vat' => round($product->price * (100 + self::VAT) / 100, 2),
                'base_price' => $product->basePrice,
                'base_price_vat' => round($product->basePrice * (100 + self::VAT) / 100, 2),
                'vat' => self::VAT
            ];
            $this->db->query("INSERT INTO `product_price`", $dataPrice);

            // Attributes
            foreach ($product->attrs as $attr) {
                $this->db->query("INSERT INTO `product_attribute`", [
                    'product_id' => $id,
                    'attribute_id' => $attr->attribute_id,
                    'lang_id' => self::LANG_ID,
                    'value' => $attr->name
                ]);
            }

            // Categories
            $catsData = [];
            foreach ($product->cats as $cat) {
                $catId = (array_key_exists($cat, $this->dbCats)) ? $this->dbCats[$cat] : false;
                if (!$catId) continue;
                $this->db->query("INSERT INTO `product_category`", [
                    'product_id' => $id,
                    'category_id' => $catId
                ]);
            }
        } else {
            // Add missing product attributes (does not edit existing)
            $dbId = $this->dbProducts[$product->id];
            $dbAttrs = $this->db->fetchPairs("SELECT `a`.`import_id`, `pa`.`id`
                FROM `attribute` AS `a`
                LEFT JOIN `product_attribute` AS `pa` ON `pa`.`attribute_id` = `a`.`id`
                WHERE `pa`.`product_id` = $dbId");
            foreach ($product->attrs as $attr) {
                $key = preg_replace('/^pa_(.+)$/', '$1', $attr->taxonomy);
                if (array_key_exists($key, $dbAttrs)) continue;

                $this->db->query("INSERT INTO `product_attribute`", [
                    'product_id' => $dbId,
                    'attribute_id' => $attr->attribute_id,
                    'lang_id' => self::LANG_ID,
                    'value' => $attr->name
                ]);
            }

            // Update base price
            $basePriceVat = round($product->basePrice * (100 + self::VAT) / 100, 2);
            $this->db->query("UPDATE `product_price` SET `base_price` = $product->basePrice, `base_price_vat` = $basePriceVat WHERE `product_id` = $dbId");
        }
    }

    private function fetchProducts()
    {
        $rows = $this->wp->fetchAll("SELECT `p`.`ID` AS `id`, `p`.`post_title` AS `name`, `p`.`post_name` AS `slug`, `p`.`post_content` AS `description`, `p`.`post_excerpt` AS `perex`,
            `sku`.`meta_value` AS `sku`, `price`.`meta_value` AS `price`, `stock`.`meta_value` AS `stock`, `attrs`.`meta_value` AS `rawAttrs`, `thumb`.`meta_value` AS `thumb`,
            `basePrice`.`meta_value` AS `basePrice`
            FROM `wp_posts` AS `p`
            LEFT JOIN `wp_postmeta` AS `sku` ON `sku`.`post_id` = `p`.`ID` AND `sku`.`meta_key` = '_sku'
            LEFT JOIN `wp_postmeta` AS `price` ON `price`.`post_id` = `p`.`ID` AND `price`.`meta_key` = '_price'
            LEFT JOIN `wp_postmeta` AS `basePrice` ON `basePrice`.`post_id` = `p`.`ID` AND `basePrice`.`meta_key` = 'festiUserRolePrices'
            LEFT JOIN `wp_postmeta` AS `attrs` ON `attrs`.`post_id` = `p`.`ID` AND `attrs`.`meta_key` = '_product_attributes'
            LEFT JOIN `wp_postmeta` AS `stock` ON `stock`.`post_id` = `p`.`ID` AND `stock`.`meta_key` = '_stock'
            LEFT JOIN `wp_postmeta` AS `thumbMeta` ON `thumbMeta`.`post_id` = `p`.`ID` AND `thumbMeta`.`meta_key` = '_thumbnail_id'
            LEFT JOIN `wp_postmeta` AS `thumb` ON `thumb`.`post_id` = `thumbMeta`.`meta_value` AND `thumb`.`meta_key` = '_wp_attached_file'
            WHERE `p`.`post_type` = 'product' AND `p`.`post_status` = 'publish'");
        $products = [];
        foreach ($rows as $row) {
            $row = ArrayHash::from(iterator_to_array($row));
            $row->cats = [];
            $row->producer = null;
            $row->attrs = [];
            $basePrice = Json::decode($row->basePrice, Json::FORCE_ARRAY);
            $row->basePrice = ($basePrice['vekoodoberate']) ? $basePrice['vekoodoberate'] : 0;

            $terms = $this->wp->fetchAll("SELECT `t`.`term_id` AS `id`, `t`.`name` AS `name`, `t`.`slug` AS `slug`, `tt`.`taxonomy` AS `taxonomy`
                FROM `wp_terms` AS `t`
                LEFT JOIN `wp_term_taxonomy` AS `tt` ON `tt`.`term_id` = `t`.`term_id`
                LEFT JOIN `wp_term_relationships` AS `ttr` ON `ttr`.`term_taxonomy_id` = `tt`.`term_taxonomy_id`
                WHERE `ttr`.`object_id` = $row->id");
            
            $usedAttrs = [];
            foreach ($terms as $term) {
                if ($term->taxonomy === 'product_cat' && $term->id != self::ROOT_CAT) $row->cats[] = $term->id;
                else if ($term->taxonomy === 'product_tag') $row->producer = $term->id;
                else if (preg_match('/^pa_(.+)$/', $term->taxonomy, $match)) {
                    if (isset($this->dbAttributes[$match[1]])) {
                        $term = ArrayHash::from(iterator_to_array($term));
                        $term->attribute_id = $this->dbAttributes[$match[1]];
                        $usedAttrs[] = $term->taxonomy;
                        $row->attrs[] = $term;
                    }
                } else if (in_array($term->taxonomy, ['product_type', 'pwb-brand', 'product_cat', 'product_visibility', 'vtmam_rule_category'])) continue;
                else {
                    echo 'UNKNOWN TERM:<pre>';
                    print_r($term);
                    exit();
                }
            }

            if ($row->rawAttrs) {
                $rawAttrs = unserialize($row->rawAttrs);
                foreach ($rawAttrs as $id => $attr) {
                    if (in_array($id, $usedAttrs) || !trim($attr['value']) || !isset($this->dbAttributes[$id])) continue;
                    $row->attrs[] = ArrayHash::from([
                        'name' => $attr['value'],
                        'attribute_id' => $this->dbAttributes[$id],
                        'taxonomy' => $id
                    ]);
                }
            }
            $products[$row->id] = $row;
        }
        return $products;
    }

    private function importProducts()
    {
        $this->dbProducts = $this->db->fetchPairs('SELECT `import_id`, `id` FROM `product` WHERE `import_id` IS NOT NULL');
        $products = $this->fetchProducts();

        foreach ($products as $product) {
            $this->importProduct($product);
        }
    }

    private function importProducer(ArrayHash $producer)
    {
        if (!array_key_exists($producer->id, $this->dbProducers)) {
            $data = [
                'import_id' => $producer->id,
                'name' => $producer->name,
                'description' => $producer->description,
                'slug' => $producer->slug,
                'image' => ($producer->thumb) ? $this->upload($producer->thumb, 'producer') : null
            ];
            $this->db->query("INSERT INTO `producer`", $data);
            $this->dbProducers[$producer->id] = $this->db->getInsertId();
        }
    }

    private function fetchProducers()
    {
        $rows = $this->wp->fetchAll("SELECT `t`.`term_id` AS `id`, `t`.`name` AS `name`,
            `tt`.`description` AS `description`, `t`.`slug` AS `slug`, `pm`.`meta_value` AS `thumb`
            FROM `wp_terms` AS `t`
            INNER JOIN `wp_term_taxonomy` AS `tt` ON `tt`.`term_id` = `t`.`term_id`
            LEFT JOIN `wp_termmeta` AS `tm` ON `tm`.`term_id` = `t`.`term_id` AND `tm`.`meta_key` = 'product_tag_img'
            LEFT JOIN `wp_postmeta` AS `pm` ON `pm`.`post_id` = `tm`.`meta_value` AND `pm`.`meta_key` = '_wp_attached_file'
            WHERE `tt`.`taxonomy` = 'product_tag'
            GROUP BY `t`.`term_id`");
        $producers = [];
        foreach ($rows as $row) {
            $arr = ArrayHash::from(iterator_to_array($row));
            $producers[$arr->id] = $arr;
        }
        return $producers;
    }

    private function importCategory(ArrayHash $cat)
    {
        if (!array_key_exists($cat->id, $this->dbCats)) {
            $data = [
                'parent_id' => ($cat->id_parent && $cat->id_parent !== self::ROOT_CAT) ? $this->dbCats[$cat->id_parent] : null,
                'import_id' => $cat->id,
                'image' => ($cat->thumb) ? $this->upload($cat->thumb, 'category') : null
            ];
            $dataLang = [
                'lang_id' => self::LANG_ID,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => ($cat->description) ? $cat->description : null
            ];
            $this->db->query("INSERT INTO `category`", $data);
            $this->dbCats[$cat->id] = $dataLang['category_id'] = $this->db->getInsertId();
            $this->db->query("INSERT INTO `category_lang`", $dataLang);
        }
        foreach ($cat->children as $child) {
            $this->importCategory($child);
        }
    }

    private function fetchCategoryTree(int $parentId = 0)
    {
        $rows = $this->wp->fetchAll("SELECT `t`.`term_id` AS `id`, `t`.`name` AS `name`,
            `tt`.`parent` AS `id_parent`, `tt`.`description` AS `description`, `t`.`slug` AS `slug`, `pm`.`meta_value` AS `thumb`
            FROM `wp_terms` AS `t`
            INNER JOIN `wp_term_taxonomy` AS `tt` ON `tt`.`term_id` = `t`.`term_id`
            LEFT JOIN `wp_termmeta` AS `tm` ON `tm`.`term_id` = `t`.`term_id` AND `tm`.`meta_key` = 'thumbnail_id'
            LEFT JOIN `wp_postmeta` AS `pm` ON `pm`.`post_id` = `tm`.`meta_value` AND `pm`.`meta_key` = '_wp_attached_file'
            WHERE `tt`.`taxonomy` = 'product_cat' AND `tt`.`parent` = $parentId
            GROUP BY `t`.`term_id`
            ORDER BY `tt`.`parent` ASC");
        $cats = [];
        foreach ($rows as $row) {
            $arr = ArrayHash::from(iterator_to_array($row));
            $cats[$arr->id] = $arr;
            $cats[$arr->id]->children = $this->fetchCategoryTree($arr->id);
        }
        return $cats;
    }

    private function upload(string $url, string $dir = 'product'): string
    {
        $url = self::WP_UPLOAD_DIR . $url;
        $filename = pathinfo($url, PATHINFO_BASENAME);
        $path = '/upload/images/' . $dir . '/' . $filename;
        $img = file_get_contents($url);
        $dir = $this->appSettingsService->getWwwDir();
        file_put_contents($dir . $path, $img);
        return $path;
    }
}