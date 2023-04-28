<?php


namespace App\Model\Feed;


use App\Model\BaseRepository;
use App\Model\LocaleRepository;
use App\Model\Product\PriceFacade;
use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Shipping\ShippingRepository;
use Nette\Application\LinkGenerator;
use Nette\Database\Context;
use Nette\Utils\FileSystem;

class FeedRepository extends BaseRepository
{

    private $productRepository, $appSettingsService, $db, $linkGenerator, $priceFacade, $localeRepository, $shippingRepository;

    public function __construct(
        ProductRepository $productRepository,
        AppSettingsService $appSettingsService,
        Context $db,
        LinkGenerator $linkGenerator,
        PriceFacade $priceFacade,
        LocaleRepository $localeRepository,
        ShippingRepository $shippingRepository
    )
    {
        $this->productRepository = $productRepository;
        $this->appSettingsService = $appSettingsService;
        $this->db = $db;
        $this->linkGenerator = $linkGenerator;
        $this->priceFacade = $priceFacade;
        $this->localeRepository = $localeRepository;
        $this->shippingRepository = $shippingRepository;
    }

    public function makeHeurekaSk($die = false)
    {
        $currencyId = $this->localeRepository->getCurrencyIdByLang('sk');
        $xml = new SimpleXMLExtended('<?xml version="1.0" encoding="utf-8"?><SHOP/>');
        $products = $this->productRepository->getAll();
        $a = 1;


        foreach( $products as $row )
        {
            $priceVat = $this->priceFacade->getUserPriceVat($row->id, $currencyId);
            $name = $row->name;
            if(!$name) {
                continue;
            }
            if($priceVat == 0) {
                continue;
            }
            $track = $xml->addChild('SHOPITEM');
            $track->addChild('ITEM_ID', $row->productId );
            $track->addChildWithCDATA('PRODUCTNAME', $name );
            $track->addChildWithCDATA('DESCRIPTION', $row->description );
            $track->addChild('PRICE_VAT', $priceVat );
            /*
                        if( $row->inStock == 1 )
                        {
                            $track->addChild('DELIVERY_DATE', 0 );
                        }
                        else
                        {
                            $deliveryDate = (int) FaConfig::gi()->getValue("notInStockDeliveryTimeHeureka");
                            $track->addChild('DELIVERY_DATE', $deliveryDate ? $deliveryDate : 7 );
                        }
            */

            $track->addChild('DELIVERY_DATE', 0 );
            $url = $this->linkGenerator->link('Front:Product:default', ['slug' => $row->id]);
            $baseUrl = substr($this->linkGenerator->link('Front:Homepage:default'), 0, -1);
            $track->addChildWithCDATA('URL', $url );
            $track->addChildWithCDATA('IMGURL', $baseUrl.$row->image);
            $track->addChildWithCDATA( 'MANUFACTURER', $row->producer->name );
            $track->addChildWithCDATA( 'EAN', $row->ean );
            $track->addChildWithCDATA('CATEGORYTEXT',  $row->category->heureka->name ?? '');
            $a++;
        }

        print $xml->asXML( $this->appSettingsService->getWwwDir() . '/feed/feed-heurekask.xml' );
        if($die) {
            die();
        }

    }

    public function makePricemaniaSk($die = false)
    {
        $xml = new SimpleXMLExtended('<?xml version="1.0" encoding="utf-8"?><products/>');
        $products = $this->productRepository->getAll();
        $a = 1;


        foreach( $products as $row )
        {
            $name = $row->name;
            if(!$name) {
                continue;
            }
            if($row->priceVat == 0) {
                continue;
            }
            $track = $xml->addChild('product');
            $track->addChild('id', $row->productId );
            $track->addChildWithCDATA('name', $name );
            $track->addChildWithCDATA('description', $row->description );
            $track->addChild('price', $row->priceVat );
            /*
                        if( $row->inStock == 1 )
                        {
                            $track->addChild('DELIVERY_DATE', 0 );
                        }
                        else
                        {
                            $deliveryDate = (int) FaConfig::gi()->getValue("notInStockDeliveryTimeHeureka");
                            $track->addChild('DELIVERY_DATE', $deliveryDate ? $deliveryDate : 7 );
                        }
            */

            if($row->inStock != null) {
                $track->addChild('availability', 0 );
            } else {
                $track->addChild('availability', 31 );
            }
            $track->addChild('shipping', 0);
            $url = $this->linkGenerator->link('Front:Product:default', ['slug' => $row->slug]);
            $baseUrl = substr($this->linkGenerator->link('Front:Homepage:default'), 0, -1);
            $track->addChildWithCDATA('url', $url );
            $track->addChildWithCDATA('picture', $baseUrl.$row->image);
            $track->addChildWithCDATA( 'manufacturer', $row->producer->name );
            $track->addChildWithCDATA( 'ean', $row->ean );
            $track->addChildWithCDATA('category',  $row->category->pricemania->path ?? '');
            $a++;
        }

        print $xml->asXML( $this->appSettingsService->getWwwDir() . '/feed/feed-pricemaniask.xml' );
        if($die) {
            die();
        }

    }

    public function getHeurekaCatsToSelect()
    {
        return $this->db->table('heureka')->fetchPairs('id', 'name');
    }

    public function getPricemaniaCatsToSelect()
    {
        return $this->db->table('pricemania')->fetchPairs('id', 'path');
    }

    public function importPricemania()
    {
        $file = file_get_contents('http://185.59.208.150/categories-tree/?language=sk');
        $xml = new \SimpleXMLElement($file);
        foreach ($xml->category as $item) {
            $this->db->table('pricemania')->insert([
                'id' => $item->id,
                'name' => $item->name,
                'path' => $item->path
            ]);
        }
    }

    public function importHeurekaCtegoriesSk()
    {
        $file = file_get_contents('https://www.heureka.sk/direct/xml-export/shops/heureka-sekce.xml');
        $xml = new \SimpleXMLElement($file);
        $categories = json_decode(json_encode($xml), true);
        foreach ($xml->CATEGORY as $category) {
            dump($category);
        }
        die;
    }

    public function getGoogleCatsToSelect()
    {
        return $this->db->table('gtaxonomy')->fetchPairs('id', 'name');
    }

    public function makeGoogleSk($die = false)
    {
        $currencyId = $this->localeRepository->getCurrencyIdByLang('sk');
        $langId = $this->localeRepository->getLangIdByLang('sk');
        $products = $this->productRepository->getActive($langId);
        ini_set("memory_limit", "-1");
        $baseUrl = $baseUrl = 'https://www.app.sk';
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $xmlRoot = $doc->createElement("rss");
        $xmlRoot = $doc->appendChild($xmlRoot);
        $xmlRoot->setAttribute('version', '2.0');
        $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:g', "http://base.google.com/ns/1.0");
        $channelNode = $xmlRoot->appendChild($doc->createElement('channel'));
        $channelNode->appendChild($doc->createElement('title', 'app.sk'));
        $channelNode->appendChild($doc->createElement('link', $baseUrl));
        $channelNode->appendChild($doc->createElement('description', 'app.sk produkty'));
        foreach ($products as $row) {
            if (strlen($row->name) < 1) {
                continue;
            }
            $origPriceVat = $this->priceFacade->getOrigPriceVat($row->id, $currencyId);
            $priceVat = $this->priceFacade->getUserPriceVat($row->id, $currencyId, true);
            if ($priceVat == 0) {
                continue;
            }
            $category = $row->category->related('category_lang')->where('lang_id', $langId)->fetch()->name;
            $itemNode = $channelNode->appendChild($doc->createElement('item'));
            $itemNode->appendChild($doc->createElement('g:id', $row->id));
            $itemNode->appendChild($doc->createElement('g:title', htmlspecialchars($row->name)));
            $itemNode->appendChild($doc->createElement('g:description', str_replace("&nbsp;", ' ', html_entity_decode(strip_tags(htmlspecialchars($row->description))))));
            if($origPriceVat > $priceVat) {
                $itemNode->appendChild($doc->createElement('g:sale_price', $priceVat . ' EUR'));
                $itemNode->appendChild($doc->createElement('g:price', $origPriceVat . ' EUR'));
            } else {
                $itemNode->appendChild($doc->createElement('g:price', $priceVat . ' EUR'));
            }
            $itemNode->appendChild($doc->createElement('g:availability', 'in stock'));
            $itemNode->appendChild($doc->createElement('link', $baseUrl . '/' . $row->slug));
            $itemNode->appendChild($doc->createElement('g:image_link', $baseUrl . $row->image));
            $itemNode->appendChild($doc->createElement('g:product_type', $category));
            if (isset($row->category->gtaxonomy_id) > 0) {
                $itemNode->appendChild($doc->createElement('g:google_product_category', $row->category->gtaxonomy->name ?? ''));
            }
            $itemNode->appendChild($doc->createElement('g:brand', $row->producer->name));
            $itemNode->appendChild($doc->createElement('g:condition', 'new'));
            $shipping = $itemNode->appendChild($doc->createElement('g:shipping'));
            $shipping->appendChild($doc->createElement('g:price', $this->shippingRepository->getPrice(0, 1, $currencyId).' EUR'));
        }
        $doc->formatOutput = true;
        FileSystem::createDir($this->appSettingsService->getWwwDir() . '/feed/');
        file_put_contents($this->appSettingsService->getWwwDir() . '/feed/feed-googlesk.xml', $doc->saveXML());
        if ($die) {
            die();
        }
        return true;
    }

    public function makeGoogleCz($die = false)
    {
        $currencyId = $this->localeRepository->getCurrencyIdByLang('cs');
        $langId = $this->localeRepository->getLangIdByLang('cs');
        $products = $this->productRepository->getActive($langId);
        ini_set("memory_limit", "-1");
        $baseUrl = 'https://www.app.cz';
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $xmlRoot = $doc->createElement("rss");
        $xmlRoot = $doc->appendChild($xmlRoot);
        $xmlRoot->setAttribute('version', '2.0');
        $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:g', "http://base.google.com/ns/1.0");
        $channelNode = $xmlRoot->appendChild($doc->createElement('channel'));
        $channelNode->appendChild($doc->createElement('title', 'app.cz'));
        $channelNode->appendChild($doc->createElement('link', $baseUrl));
        $channelNode->appendChild($doc->createElement('description', 'app.cz produkty'));
        foreach ($products as $row) {
            if (strlen($row->name) < 1) {
                continue;
            }
            $origPriceVat = $this->priceFacade->getOrigPriceVat($row->id, $currencyId);
            $priceVat = $this->priceFacade->getUserPriceVat($row->id, $currencyId, true);
            if ($priceVat == 0) {
                continue;
            }
            $category = $row->category->related('category_lang')->where('lang_id', $langId)->fetch()->name;
            $itemNode = $channelNode->appendChild($doc->createElement('item'));
            $itemNode->appendChild($doc->createElement('g:id', $row->id));
            $itemNode->appendChild($doc->createElement('g:title', htmlspecialchars($row->name)));
            $itemNode->appendChild($doc->createElement('g:description', str_replace("&nbsp;", ' ', html_entity_decode(strip_tags(htmlspecialchars($row->description))))));
            if($origPriceVat > $priceVat) {
                $itemNode->appendChild($doc->createElement('g:sale_price', $priceVat . ' CZK'));
                $itemNode->appendChild($doc->createElement('g:price', $origPriceVat . ' CZK'));
            } else {
                $itemNode->appendChild($doc->createElement('g:price', $priceVat . ' CZK'));
            }
            $itemNode->appendChild($doc->createElement('g:availability', 'in stock'));
            $itemNode->appendChild($doc->createElement('link', $baseUrl . '/' . $row->slug));
            $itemNode->appendChild($doc->createElement('g:image_link', $baseUrl . $row->image));
            $itemNode->appendChild($doc->createElement('g:product_type', $category));
            if (isset($row->category->gtaxonomy_id) > 0) {
                $itemNode->appendChild($doc->createElement('g:google_product_category', $row->category->gtaxonomy->name ?? ''));
            }
            $itemNode->appendChild($doc->createElement('g:brand', $row->producer->name));
            $itemNode->appendChild($doc->createElement('g:condition', 'new'));
            $shipping = $itemNode->appendChild($doc->createElement('g:shipping'));
            $shipping->appendChild($doc->createElement('g:price', $this->shippingRepository->getPrice(0, 4, $currencyId).' CZK'));
        }
        $doc->formatOutput = true;
        FileSystem::createDir($this->appSettingsService->getWwwDir() . '/feed/');
        file_put_contents($this->appSettingsService->getWwwDir() . '/feed/feed-googlecz.xml', $doc->saveXML());
        if ($die) {
            die();
        }
        return true;
    }

    public function makeGoogleAll($die = false)
    {
        $this->makeGoogleSk();
        $this->makeGoogleCz();
        if ($die) {
            die();
        }
    }

}