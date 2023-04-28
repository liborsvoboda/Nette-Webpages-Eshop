<?php

namespace App\Model\Import;

use App\Model\Attribute\AttributeRepository;
use App\Model\Category\CategoryRepository;
use App\Model\Producer\ProducerRepository;
use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use Nette\Database\Context;
use Nette\Database\Connection;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class CMPCartImportService {

    private $appSettingsService,
        $productRepository,
        $categoryRepository,
        $producerRepository,
        $attributeRepository,
        $db,
        $langId = 1;

    public function __construct(
        AppSettingsService $appSettingsService, ProductRepository $productRepository, CategoryRepository $categoryRepository, ProducerRepository $producerRepository, AttributeRepository $attributeRepository, Context $db
    ) {
        $this->appSettingsService = $appSettingsService;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->producerRepository = $producerRepository;
        $this->attributeRepository = $attributeRepository;
        $this->db = $db;
    }

    private function getOldDb()
    {
        $olddb = new Connection('mysql:host=127.0.0.1;dbname=bazenyliptov_CMPcart', 'bazenyliptov_dbu', 'W2nndhZjLv_DV');
        return $olddb;
    }

    public function importProducts() {
        $olddb = $this->getOldDb();
        $products = $olddb->query('SELECT * FROM product')->fetchAll();
        $i = 0;

        foreach ($products as $product) {
            $test = $this->db->table('product')->where('id', $product->id)->fetch();
            if($test) {
                continue;
            }
            $image = null;
            if ($product->image) {
                $img = @file_get_contents('https://www.bazenyliptov.sk/' . $product->image);

                if (strlen($img) > 0) {

                    $filename = explode('/', $product->image);
                    $filename = $filename[count($filename) - 1];
                    $filenamewithpath = realPath('upload/images/product') . '/' . $filename;
                    file_put_contents($filenamewithpath, $img);
                    $image = '/upload/images/product/' . $filename;
                }
            }

            $productValues = [
                'id' => $product->id,
                'sku' => $product->code,
                'category_id' => $product->category,
                'active' => $product->active,
                'image' => $image,
                'ean'=> $product->ean,
                'producer_id'=> $product->creator,
                'inStock' => $product->inStock,
                'saleTag' => $product->onsale,
                'featured'=> $product->onhome,
                'discountTag'=> $product->isAction,
                'stock'=> $product->delivery,
                'weight'=> $product->weight,
                'video'=> $product->video
            ];
            $this->db->query('SET FOREIGN_KEY_CHECKS=0;');
            $newProduct = $this->db->table('product')->insert($productValues);

            $price = $product->price;
            $oldPrice = (float)$product->priceOld;
            $oldPriceVat = round($oldPrice * 1.2, 2);
            $priceVat = round($price * 1.2, 2);
            $vat = 20;
            $priceValues = [
                'product_id' => $product->id,
                'currency_id' => 1,
                'price' => $price,
                'price_vat' => $priceVat,
                'vat' => $vat,
                'orig_price' => $oldPrice,
                'orig_price_vat'=> $oldPriceVat,
            ];
            $this->db->query('SET FOREIGN_KEY_CHECKS=0;');
            $this->db->table('product_price')->insert($priceValues);

            
            $productLang = $olddb->query('
        SELECT * FROM  productLang
         WHERE productId = ' . $product->id
            )->fetch();

            if(!$productLang) {
                continue;
            }
                $productLangValues = [
                    'lang_id' => 1,
                    'product_id' => $product->id,
                    'name' => $productLang->name,
                    'description' => $productLang->description,
                    'slug' => $productLang->normalize,
                    'perex'=> $productLang->perex
                ];
                $this->db->query('SET FOREIGN_KEY_CHECKS=0;');
                $this->db->table('product_lang')->insert($productLangValues);
            
//
//
            $productGallery = $olddb->query('
        SELECT * FROM   productGallery
         WHERE productId = ' . $product->id
            )->fetchAll();
            foreach ($productGallery as $productGalleryItem) {
                $image = null;
                if ($productGalleryItem->image) {
                    $img = @file_get_contents('https://www.bazenyliptov.sk/' . $productGalleryItem->image);

                    if (strlen($img) > 0) {

                        $filename = explode('/', $productGalleryItem->image);
                        $filename = $filename[count($filename) - 1];
                        $filenamewithpath = realPath('upload/images/product') . '/' . $filename;
                        file_put_contents($filenamewithpath, $img);
                        $image = '/upload/images/product/' . $filename;

                        $productGalleryValues = [
                            'product_id' => $newProduct->id,
                            'image' => $image,
                            'ord' => 1
                        ];
                        $this->db->query('SET FOREIGN_KEY_CHECKS=0;');
                        $this->db->table('product_gallery')->insert($productGalleryValues);
                    }
                }
            }
//
//
//            $i++;
//            if ($i == 20) {
//                //     die();
//            }
        }
    }

    public function importCategories() {
        $olddb = $this->getOldDb();
        $categories = $olddb->query('
                            SELECT * FROM category
                            ORDER BY category.id ')->fetchAll();
        foreach ($categories as $category) {
            $image = null;
            if ($category->image) {
                $img = @file_get_contents('https://www.bazenyliptov.sk/' . $category->image);
                if (strlen($img) > 0) {

                    $filename = explode('/', $category->image);
                    $filename = $filename[count($filename) - 1];
                    $filenamewithpath = realPath('upload/images/category') . '/' . $filename;
                    file_put_contents($filenamewithpath, $img);
                    $image = '/upload/images/category/' . $filename;
                }
            }
            $heureka = null;
            if (strlen($category->heureka) > 0) {
                $heu = $olddb->query('SELECT * FROM heureka WHERE name = ?', $category->heureka)->fetch();
                if ($heu) {
                    $heureka = $heu->id;
                }
            }
            $newCat = [
                'id' => $category->id,
                'parent_id' => $category->parent == 0 ? null : $category->parent,
                'visible' => $category->active,
                'heureka_id' => $heureka,
                'image' => $image,
                'gtaxonomy_id'=> $category->gtaxanomy
            ];
            $this->db->query('SET FOREIGN_KEY_CHECKS=0;');
            $this->db->table('category')->insert($newCat);


            $categoryLang = $olddb->query('
        SELECT * FROM categoryLang
        WHERE categoryId = ' . $category->id . '
        ')->fetchAll();
            foreach ($categoryLang as $categoryLangItem) {
                $newCatLang = [
                    'lang_id' => $categoryLangItem->langId,
                    'category_id' => $category->id,
                    'name' => $categoryLangItem->name,
                    'description' => $categoryLangItem->description,
                    'slug' => $categoryLangItem->normalize
                ];
                $this->db->table('category_lang')->insert($newCatLang);
            }
        }
    }

    public function importProducers()
    {
        $oldDb = $this->getOldDb();
        $producers = $oldDb->fetchAll('SELECT * FROM creatorLang');
        foreach ($producers as $producer) {
            $this->db->table('producer')->insert([
                'id' => $producer->creatorId,
                'name' => $producer->name,
                'slug' => $producer->normalize ?? Strings::webalize($producer->name.'-'.$producer->creatorId)//webalize viz. doc.nette
            ]);
        }
    }


}
