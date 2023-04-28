<?php


namespace App\Model\MarketingGallery;


use App\Model\BaseRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\UploadService;
use Nette\Database\Context;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class MarketingGalleryRepository extends BaseRepository
{
    private $db, $table = 'marketing_gallery_image';
    protected $appSettingsService;

    public function __construct(Context $db, AppSettingsService $appSettingsService)
    {
        $this->db = $db;
        $this->appSettingsService = $appSettingsService;
    }

    public function getAll($galleryId)
    {
        return $this->db->table($this->table)->where('gallery_id', $galleryId);
    }

    
    public function getAllGalleries()
    {
        return $this->db->table('marketing_gallery');
    }

    public function getGalleryById($id)
    {
        return $this->getAllGalleries()->where('id', $id);
    }

    public function getAllGalleriesWithFirstImage()
    {
        $galleries = [];
        $galleriesDb = $this->db->table('marketing_gallery')->fetchAll();
        foreach ($galleriesDb as $item) {
            $galleries[$item->id] = ArrayHash::from($item->toArray());
            $firstImage = $this->getAll($item->id)->order('ord')->limit(1)->fetch();
            if($firstImage) {
                $galleries[$item->id]->image = $firstImage->image;
            }
        }
        return $galleries;
    }

    public function addGallery($values)
    {
        $this->getAllGalleries()->insert($values);
    }


    public function add($values, $galleryId)
    {
        $image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/marketing-gallery/'.$galleryId);
        $data = [
            'gallery_id' => $galleryId,
            'image' => $image,
            'ord' => 1,
        ];
        $this->db->table($this->table)->insert($data);
    }
    
    public function remove($galleryId)
    {
        $image = $this->db->table('marketing_gallery_image')->where('gallery_id', $galleryId);

        if(!$image) {
            return;
        }
        $this->db->table("marketing_gallery")->where('id',$galleryId)->delete();
    }

    public function updateGallery($values, $galleryId) {

        if (strlen($values->slug) < 1) {
            $values->slug = Strings::webalize($values->title);
        }
        $this->getGalleryById($galleryId)
            ->update($values);
    }


}