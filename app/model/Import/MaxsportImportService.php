<?php


namespace App\Model\Import;


use App\Model\LocaleRepository;
use App\Model\Services\AppSettingsService;
use Nette\Database\Context;
use Nette\Utils\Strings;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class MaxsportImportService
{
    private $db, $appSettingsService, $localeRepository;

    public function __construct(Context $db, AppSettingsService $appSettingsService, LocaleRepository $localeRepository)
    {
        $this->db = $db;
        $this->appSettingsService = $appSettingsService;
        $this->localeRepository = $localeRepository;
    }

    public function import($filename)
    {
        $langs = ['sk', 'cz', 'en'];
        $urls = ['https://maxsport.sk/', 'https://tvujmaxsport.cz/', 'https://maxsportnutrition.com/'];
        $vats = [20, 15, 0];
        $i = 0;
        foreach ($langs as $lang) {
            $file = $this->appSettingsService->getWwwDir() . '/../' . $filename;
            $oldDomain = $urls[$i];
            $vat = $vats[$i];
            $langId = $this->localeRepository->getByLang($lang == 'cz' ? 'cs' : $lang)->fetch()->lang_id;
            $currencyId = $this->localeRepository->getByLang($lang == 'cz' ? 'cs' : $lang)->fetch()->currency_id;
            $reader = new Xlsx();
            $reader->setLoadSheetsOnly(strtoupper($lang));
            $spreadsheet = $reader->load($file);
            $dataArray = $spreadsheet->getActiveSheet()
                ->rangeToArray(
                    'A3:AO100',     // The worksheet range that we want to retrieve
                    NULL,        // Value that should be returned for empty cells
                    TRUE,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
                    false,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
                    TRUE         // Should the array be indexed by cell row and cell column
                );
            $a = 1;
            foreach ($dataArray as $item) {
                if ($item['A'] == null) {
                    continue;
                }
                echo $a . ' - ' . $item['A'] . '-' . $item['P'] . '<br>';
                $a++;
                $productId = null;
                //$catId = $this->saveCategory($item['M'], $item['L'], $item['N'], $item['K'], $langId, $item['AO']);
                //$this->saveCategoryLang($item['AO'], $langId, $item['N'], $item['K'], $item['M'], $item['L']);
                $test = $this->db->table('product')->where('ean', $item['B'])->fetch();
                $product = [
                    'sku' => $item['A'],
                    'ean' => $item['B'],
                    'category_id' => $item['AO'],
                    'producer_id' => 1
                ];
                if ($test) {
                    $productId = $test->id;
                    $this->db->table('product')->where('id', $productId)->update($product);
                } else {
                    $image = file_get_contents($item['P']);
                    $imgName = pathinfo($item['P']);
                    $newImgName = '/upload/images/product/' . $imgName['basename'];
                    file_put_contents($this->appSettingsService->getWwwDir() . $newImgName, $image);
                    $product['image'] = $newImgName;
                    $productId = $this->db->table('product')->insert($product);
                    $this->saveProductGallery($item['Q'], $productId);
                }
                $productLang = [
                    'product_id' => $productId,
                    'lang_id' => $langId,
                    'name' => $item['D'],
                    'perex' => $item['I'],
                    'slug' => Strings::webalize($item['D'] . '-' . $productId),
                    'description' => $item['J'],
                    'old_url' => str_replace($oldDomain, '', $item['C'])
                ];
                $this->db->table('product_lang')->where('product_id', $productId)->where('lang_id', $langId)->delete();
                $this->db->table('product_lang')->insert($productLang);
                $basePrice = str_replace(',', '.', $item['G']);
                $productPrices = [
                    'product_id' => $productId,
                    'currency_id' => $currencyId,
                    'price_vat' => $item['E'],
                    'price' => round($item['E'] / ((100 + $vat) / 100), 2),
                    'base_price' => $basePrice,
                    'base_price_vat' => round($basePrice * ((100 + $vat) / 100), 2),
                    'vat' => $vat
                ];
                $this->db->table('product_price')->where('product_id', $productId)->where('currency_id', $currencyId)->delete();
                $this->db->table('product_price')->insert($productPrices);
                $this->db->table('product_attribute')->where('product_id', $productId)->where('lang_id', $langId)->delete();
                $attributes = [
                    1 => 'T',
                    2 => 'U',
                    3 => 'V',
                    4 => 'W',
                    5 => 'X',
                    6 => 'Y',
                    7 => 'Z',
                    8 => 'AA',
                    9 => 'AB',
                    10 => 'AC',
                    11 => 'AD',
                    12 => 'AE',
                    13 => 'AF',
                    14 => 'AG',
                    15 => 'AH',
                    16 => 'AI',
                    17 => 'AJ',
                    18 => 'AK',
                    19 => 'AL',
                    20 => 'AM',
                    21 => 'AN',
                ];
                foreach ($attributes as $key => $value) {
                    if ($item[$attributes[$key]]) {
                        $this->db->table('product_attribute')->insert([
                            'product_id' => $productId,
                            'lang_id' => $langId,
                            'attribute_id' => $key,
                            'value' => $item[$value]
                        ]);
                    }
                }
                unset($product, $productLang, $productPrices, $productId, $basePrice);
                echo $a . ' - ' . $item['A'] . '-' . $item['P'] . '<br>';
            }
            $i++;
        }
    }

    private function saveProductGallery($images, $productId)
    {
        $itemsa = explode(';', $images);
        if (count($itemsa) < 1) {
            return;
        }
        foreach ($itemsa as $itema) {
            $image = file_get_contents($itema);
            $imgName = pathinfo($itema);
            $newImgName = '/upload/images/product/' . $imgName['basename'];
            file_put_contents($this->appSettingsService->getWwwDir() . $newImgName, $image);
            $productGallery = ['product_id' => $productId, 'image' => $newImgName, 'ord' => 1];
            //$this->db->table('product_gallery')->insert($productGallery);
        }
    }

    private function saveCategory($main, $mainDesc, $sub, $subDesc, $langId, $categoryId)
    {
        $mainCat = $this->db->table('category_lang')->where('name', $main)->where('lang_id', $langId)->fetch();
        if (!$mainCat) {
            $category = [
                'parent_id' => null,
                'visible' => true
            ];
            $mainId = $this->db->table('category')->insert($category);
            $categoryLang = [
                'lang_id' => $langId,
                'category_id' => $mainId,
                'name' => $main,
                'description' => $mainDesc,
                'slug' => Strings::webalize($main . '-' . $mainId)
            ];
            $this->db->table('category_lang')->insert($categoryLang);
            $parentId = $mainId;
        } else {
            $parentId = $mainCat->id;
        }
        $subCat = $this->db->table('category_lang')
            ->where('category_lang.lang_id', $langId)
            ->where('category_lang.name', $sub)
            ->where('category.parent_id', $parentId)
            ->fetch();
        if (!$subCat) {
            $category = [
                'parent_id' => $parentId,
                'visible' => true
            ];
            $subId = $this->db->table('category')->insert($category);
            $categoryLang = [
                'lang_id' => $langId,
                'category_id' => $subId,
                'name' => $sub,
                'description' => $subDesc,
                'slug' => Strings::webalize($sub . '-' . $subId)
            ];
            $this->db->table('category_lang')->insert($categoryLang);
            $subCatId = $subId->id;
        } else {
            $subCatId = $subCat->category_id;
        }
        return $subCatId;
    }

    private function saveCategoryLang($categoryId, $langId, $name, $desc, $main, $mainDesc)
    {
        $test = $this->db->table('category_lang')->where('category_id', $categoryId)->where('lang_id', $langId)->fetch();
        if (!$test) {
            $this->db->table('category_lang')->insert([
                'lang_id' => $langId,
                'category_id' => $categoryId,
                'name' => $name,
                'description' => $desc,
                'slug' => Strings::webalize($name . '-' . $categoryId)
            ]);
        }
        $cat = $this->db->table('category')->where('id', $categoryId)->fetch();
        $parentCat = $this->db->table('category')->where('id', $cat->parent_id)->fetch();
        $parent = $this->db->table('category_lang')->where('category_id', $parentCat->id)->where('lang_id', $langId)->fetch();
        if (!$parent) {
            $this->db->table('category_lang')->insert([
                'lang_id' => $langId,
                'category_id' => $parentCat->id,
                'name' => $main,
                'description' => $mainDesc,
                'slug' => Strings::webalize($main . '-' . $parentCat->id)
            ]);
        }
    }

    public function importOldcategoryUrl($filename)
    {
        $urls = $this->csvToArray($filename);
        foreach ($urls as $url) {
            var_dump($url);
            $this->db->table('category_lang')
                ->where('lang_id', $url[1])
                ->where('category_id', $url[2])
                ->update(['old_url' => $url[3]]);
        }
    }

    public function importUsers($filename)
    {
        $oldUsers = $this->csvToArray($filename);
        foreach ($oldUsers as $oldUser) {
            $level = strpos($oldUser['13'],'partner') !== false ? 2 : 1;
            $data = [
                'email' => $oldUser['5'],
                'firstName' => $oldUser['9'],
                'lastName' => $oldUser['10'],
                'password' => $oldUser['2'],
                'registered_at' => $oldUser['7'],
                'user_level_id' => $level
            ];
            $test = $this->db->table('user')->where('email', $oldUser['5'])->fetch();
            if(!$test) {
                $this->db->table('user')->insert($data);
            } else {
                $this->db->table('user')->where('email', $oldUser['5'])->update($data);
            }
        }
    }

    private function csvToArray($filename)
    {
        $file = fopen($this->appSettingsService->getWwwDir() . '/../' . $filename, 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            $array[] = $line;
        }
        fclose($file);
        return $array;
    }

    public function importCzPrices($filename)
    {
        $prices = $this->csvToArray($filename);
        foreach ($prices as $price) {
            $pr = [
                'price_vat' => $price['1'],
                'price' => (float)str_replace(',', '.',$price['1'] ) / 1.15,
                'base_price' =>  $price['2'],
                'base_price_vat' => str_replace(',', '.', $price['2']) * 1.15,
                'vat' => 15
            ];
            $product = $this->db->table('product')->where('sku', $price[0])->fetch();
            if(!$product) {
                continue;
            }
            $this->db->table('product_price')
                ->where('product_id', $product->id)
                ->where('currency_id', 2)
                ->update($pr);
        }
        die;
    }

    public function importPrices($filename)
    {
        $langs = ['sk', 'cz'];
        $vats = [20, 15, 0];
        $i = 0;
        foreach ($langs as $lang) {
            $file = $this->appSettingsService->getWwwDir() . '/../' . $filename;
            $vat = $vats[$i];
            $langId = $this->localeRepository->getByLang($lang == 'cz' ? 'cs' : $lang)->fetch()->lang_id;
            $currencyId = $this->localeRepository->getByLang($lang == 'cz' ? 'cs' : $lang)->fetch()->currency_id;
            $reader = new Xlsx();
            $reader->setLoadSheetsOnly(strtoupper($lang));
            $spreadsheet = $reader->load($file);
            $dataArray = $spreadsheet->getActiveSheet()
                ->rangeToArray(
                    'A3:AO100',     // The worksheet range that we want to retrieve
                    NULL,        // Value that should be returned for empty cells
                    TRUE,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
                    false,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
                    TRUE         // Should the array be indexed by cell row and cell column
                );
            foreach ($dataArray as $item) {
                $test = $this->db->table('product')->where('ean', $item['B'])->fetch();
                if(!$test) {
                    continue;
                }
                $productId = $test->id;
                $productLang = [
                    'lang_sku' => $item['A']
                ];
                $this->db->table('product_lang')->where('product_id', $productId)->where('lang_id', $langId)->update($productLang);
                $basePrice = str_replace(',', '.', $item['G']);
                $productPrices = [
                    'product_id' => $productId,
                    'currency_id' => $currencyId,
                    'price_vat' => $item['E'],
                    'price' => round($item['E'] / ((100 + $vat) / 100), 2),
                    'base_price' => $basePrice,
                    'base_price_vat' => round($basePrice * ((100 + $vat) / 100), 2),
                    'vat' => $vat
                ];
                $this->db->table('product_price')->where('product_id', $productId)->where('currency_id', $currencyId)->delete();
                $this->db->table('product_price')->insert($productPrices);
            }
            $i++;
        }
    }
}