<?php


namespace App\FrontModule\Presenters;


use App\Model\MarketingGallery\MarketingGalleryRepository;
use Nette\Application\BadRequestException;

class GalleryPresenter extends BasePresenter
{
    /**
     * @var MarketingGalleryRepository
     * @inject
     */
    public $galleryRepository;

    public function actionDefault()
    {
        $galleries = $this->galleryRepository->getAllGalleriesWithFirstImage();
        $this->template->galleries = $galleries;
    }

    public function actionGallery($slug)
    {
        $images = $this->galleryRepository->getAll($slug);
        if(!$images) {
            throw new BadRequestException();
        }
        $gallery = $this->galleryRepository->getGalleryById($slug)->fetch();
        $this->template->images = $images;
        $this->template->gallery = $gallery;
    }
}