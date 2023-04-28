<?php


namespace App\Model\Slider;


use App\Model\BaseRepository;
use App\Model\Category\CategoryRepository;
use App\Model\Product\ProductRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\UploadService;
use Nette\Database\Context;
use Nette\Utils\ArrayHash;

class SliderRepository extends BaseRepository
{
    private $db, $appSettingsService, $table = 'slider', $productRepository, $categoryRepository;

    public function __construct(Context $db, AppSettingsService $appSettingsService, ProductRepository $productRepository, CategoryRepository $categoryRepository)
    {
        $this->db = $db;
        $this->appSettingsService = $appSettingsService;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getAllByLang(int $lang_id) {
        return $this->getAll()->where('lang_id', $lang_id)->order('ord ASC');
    }

    public function getById($id)
    {
        return $this->getAll()->where('id', $id);
    }

    public function add($values)
    {
        if($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/slider/');
        }
        if($values->image === null) {
            unset($values->image);
        }
        if($values->background) {
            $values->background = UploadService::upload($values->background, $this->appSettingsService->getWwwDir(), '/upload/images/slider/');
        }
        if($values->background === null) {
            unset($values->background);
        }
		if (!isset($values->locale_id)) $values->locale_id = 0;
        $this->db->table($this->table)->insert($values);
    }

    public function remove($id)
    {
        $this->db->table($this->table)->where('id', $id)->delete();
    }

    public function update($values, $id)
    {
        if(isset($values->background)) {
            $values->background = UploadService::upload($values->background, $this->appSettingsService->getWwwDir(), '/upload/images/slider/');
        }
        if($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/slider/');
        }
        if($values->image === null) {
            unset($values->image);
        }
        if($values->background === null) {
            unset($values->background);
        }
        $this->db->table($this->table)->where('id', $id)->update($values);
    }

    public function getAllBanners()
    {
        return $this->db->table('banner');
    }

    public function getAllBannersWithProductCount($langId = null)
    {
        $langId = $langId ?? $this->langId();
        $out = new ArrayHash();
        $banners = $this->getAllBanners();
        $a = 1;
        foreach ($banners as $banner) {
            $sub = (string) $banner->category_id;
            $subIds = $this->categoryRepository->getSubIds($banner->category_id);
            if(strlen($subIds) > 0) {
                $sub = $sub.','.$subIds;
            }
            $count = $this->productRepository->getInCategories($sub)->count('*');
            $category = $this->categoryRepository->getById($banner->category_id);
            $out[$a] = [
                'category' => $category,
                'image' => $banner->image,
                'productsCount' => $count
            ];
            $a++;
        }
        return $out;
    }

    public function getBannerById($id)
    {
        return $this->getAllBanners()->where('id', $id);
    }

    public function addBanner($values)
    {
        if($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/banner/');
        }
        if($values->image === null) {
            unset($values->image);
        }
        $this->db->table('banner')->insert($values);
    }

    public function removeBanner($id)
    {
        $this->db->table('banner')->where('id', $id)->delete();
    }

    public function updateBanner($values, $id)
    {
        if($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/slider/');
        }
        if($values->image === null) {
            unset($values->image);
        }
        $this->db->table('banner')->where('id', $id)->update($values);
    }

}