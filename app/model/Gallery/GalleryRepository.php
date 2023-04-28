<?php


namespace App\Model\Gallery;


use App\Model\BaseRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\UploadService;
use Nette\Database\Context;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class GalleryRepository extends BaseRepository
{
    private $db, $table = 'gallery_image';
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
        return $this->db->table('gallery');
    }

    public function getGalleryById($id)
    {
        return $this->getAllGalleries()->where('id', $id);
    }

    public function getAllGalleriesWithFirstImage()
    {
        $galleries = [];
        $galleriesDb = $this->db->table('gallery')->fetchAll();
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
        $image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/gallery/'.$galleryId);
        $data = [
            'gallery_id' => $galleryId,
            'image' => $image,
            'ord' => 1,
        ];
        $this->db->table($this->table)->insert($data);
    }
    
    public function remove($galleryId)
    {
        $image = $this->db->table('gallery')->where('id',$galleryId)->fetch();

        if(!$image) {
            return;
        }
        $this->db->table($this->table)->where('id',$galleryId)->delete();
    }

    public function updateGallery($values, $galleryId) {

        if (strlen($values->slug) < 1) {
            $values->slug = Strings::webalize($values->title);
        }
        $this->getGalleryById($galleryId)
            ->update($values);
    }


}