<?php


namespace App\Model\ProductGallery;


use App\Model\BaseRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\UploadService;
use Nette\Database\Context;

class ProductGalleryRepository extends BaseRepository
{
    protected $db, $table = 'product_gallery', $appSettingsService;

    public function __construct(Context $db, AppSettingsService $appSettingsService)
    {
        $this->db = $db;
        $this->appSettingsService = $appSettingsService;
    }

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getById($id)
    {
        return $this->getAll()->where('id', $id);
    }

    public function getAllForProduct($productId)
    {
        return $this->getAll()->where('product_id', $productId)->order('ord');
    }

    public function remove($id)
    {
        $image = $this->getById($id)->fetch();
        if(!$image) {
            return;
        }
        $this->getAll()->where('id', $id)->delete();
        unlink($this->appSettingsService->getWwwDir().$image->image);
    }

    public function add($values, $productId)
    {
        $image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/product/');
        $data = [
            'product_id' => $productId,
            'image' => $image,
            'ord' => 1
        ];
        $this->db->table($this->table)->insert($data);
    }

    public function updateOrder($id, $order)
    {
        $this->db->table($this->table)->where('id', $id)->update(['ord' => $order]);
    }
}