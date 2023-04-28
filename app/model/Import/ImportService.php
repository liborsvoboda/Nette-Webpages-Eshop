<?php


namespace App\Model\Import;


use App\Model\Attribute\AttributeRepository;
use App\Model\Category\CategoryRepository;
use App\Model\Producer\ProducerRepository;
use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use Nette\Database\Context;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class ImportService
{

    private $appSettingsService,
        $productRepository,
        $categoryRepository,
        $producerRepository,
        $attributeRepository,
        $db,
        $langId = 1;

    public function __construct(
        AppSettingsService $appSettingsService,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ProducerRepository $producerRepository,
        AttributeRepository $attributeRepository,
        Context $db
    )
    {
        $this->appSettingsService = $appSettingsService;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->producerRepository = $producerRepository;
        $this->attributeRepository = $attributeRepository;
        $this->db = $db;
    }

    public function importHeurekaFeed($feed, $local = false)
    {
        if ($local) {
            $url = $this->appSettingsService->getWwwDir() . '/../' . $feed;
        } else {
            $url = $feed;
        }
        $xml = simplexml_load_file($url, 'simplexmlelement', LIBXML_NOCDATA);
        $a = 0;
        foreach ($xml->shopitem as $shopitem) {
            $a++;
            $item = json_decode(json_encode($shopitem), true);
            if (!isset($item['categorytext'])) {
                continue;
            }
            $product = new ArrayHash();
            $product->sku = isset($item['item_id']) ? $item['item_id'] : null;
            $existId = null;
            if ($product->sku) {
                $existId = $this->productRepository->getIdBySku($product->sku);
            }
            if ($existId) {
                $this->productRepository->updatePrice([
                    'origPrice' => $item['price'],
                    'origPriceVat' => $item['price_vat']
                ],$existId);
                echo $a . ',';
                continue;
            }
            $iname = null;
            if (!is_array($item['imgurl'])) {
                $image = file_get_contents($item['imgurl']);
                $riname = pathinfo($item['imgurl'], PATHINFO_BASENAME);
                $iname = '/upload/images/product/' . $riname;
                file_put_contents($this->appSettingsService->getWwwDir() . $iname, $image);
            }


            if ($a > 10) {
//                die;
            }
            $categories = explode('|', str_replace('Heureka.cz | ', '', $item['categorytext']));
            $parent = null;
            foreach ($categories as $category) {
                $parent = $this->categoryRepository->create(Strings::trim($category), $parent);
            }
            if (isset($item['brand'])) {
                if ($item['brand'] == 'Nezadaná') {
                    $brand = $item['manufacturer'];
                } else {
                    $brand = $item['brand'];
                }
                $producer = $this->producerRepository->getIdByName($item['brand']);
                if (!$producer) {
                    $producerNew = $this->producerRepository->add($item['brand']);
                    $producer = $producerNew->id;
                }
            } else {
                $producer = null;
            }
            $product->category_id = $parent;
            $product->producer_id = $producer;
            $product->ean = isset($item['ean']) ? $item['ean'] : null;
            $product->image = $iname;
            $product->ean = isset($item['ean']) && !is_array($item['ean']) ? $item['ean'] : null;
            $product->unit = isset($item['unit']) ? $item['unit'] : null;
            $product->instock = isset($item['instock']) && $item['instock'] != '0' ? $item['instock'] : null;
            $existId = null;


            $productLang = new ArrayHash();
            $product->price = $item['price'];
            $product->origPrice = $item['price'];
            $product->priceVat = $item['price_vat'];
            $product->origPriceVat = $item['price_vat'];
            $product->vat = $item['vat'];
            if ($existId) {
                echo $a . ',';
                continue;
                $pId = $existId;
                $existProduct = $this->db->table('product_lang')->where('product_id', $pId)->fetch();
                $slug = $existProduct->slug;
                $this->db->table('product_lang')->where('product_id', $pId)->delete();
            } else {
                $pId = $this->db->table('product')->insert($product);
                $slug = Strings::webalize($item['productname'] . '-' . $pId);
            }
            $productLang->product_id = $pId;
            $productLang->name = $item['productname'];
            $productLang->description = is_array($item['description']) ? '' : $item['description'];
            $productLang->perex = '';
            $productLang->slug = $slug;
            $productLang->lang_id = $this->langId;
            $this->db->table('product_lang')->insert($productLang);

            /*
            if (isset($item['param'])) {
                foreach ($item['param'] as $params) {
                    dump($item['param']);die;
                    $pId = $this->attributeRepository->getIdByName($params['param_name']);
                    if (!$pId) {
                        $pId = $this->attributeRepository->add($params['param_name'], $this->langId);
                    }
                    $pVal = $params['val'];
                    $param = 'attr' . $pId;
                    $product->{$param} = $pVal;
                }
            }
            $this->attributeRepository->updateProductAttributes($product, $pId);
            */
        }
        $this->productRepository->recalculatePrice();
    }
}