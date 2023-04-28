<?php


namespace App\Model\Services;


use Nette\Utils\Image;
use Nette\Utils\Strings;
use Nette\Utils\UnknownImageFileException;

class ImageService
{

    const XS_SIZE = 100,
        SM_SIZE = 200,
        MD_SIZE = 300,
        LG_SIZE = 400,
        XL_SIZE = 600;

    const SIZES = [
        'xs' => self::XS_SIZE,
        'sm' => self::SM_SIZE,
        'md' => self::MD_SIZE,
        'lg' => self::LG_SIZE,
        'xl' => self::XL_SIZE
    ];

    private $appSettingsService;

    public function __construct(AppSettingsService $appSettingsService)
    {
        $this->appSettingsService = $appSettingsService;
    }

    private function basePath()
    {
        return $this->appSettingsService->getWwwDir();
    }

    public function getImage($args)
    {
        $fileName = $args[0];
        if (Strings::startsWith($fileName, 'http://') || Strings::startsWith($fileName, 'https://')) {
            return $fileName;
        }
        if (!isset($args[1])) {
            return $fileName;
        }
        $fileSize = $args[1];
        $info = pathinfo($this->basePath() . $fileName);
        $name = $info['basename'];
        $path = $info['dirname'];
        $newPath = $path . DIRECTORY_SEPARATOR . $fileSize . '__' . $name;
        if (!file_exists($newPath) && file_exists($path . DIRECTORY_SEPARATOR . $name)) {
            try {
                @$oldImage = Image::fromFile($path . DIRECTORY_SEPARATOR . $name);
            } catch (UnknownImageFileException $e) {
                return $fileName;
            }
            if ($oldImage->width >= $oldImage->height) {
                $oldImage->resize(self::SIZES[$fileSize], null);
            } else {
                $oldImage->resize(null, self::SIZES[$fileSize]);
            }
            $oldImage->save($newPath);

        }
        if (file_exists($newPath)) {
            return str_replace($name, $fileSize . '__' . $name, $fileName);
        } else {
            return $fileName;
        }
    }

}