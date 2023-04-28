<?php


namespace App\Model\Services;


use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;

class UploadService
{
    private $appSettingsService;

    public function __construct(AppSettingsService $appSettingsService)
    {
        $this->appSettingsService = $appSettingsService;
    }

    public static function upload(FileUpload $file, $basePath, $path, $imageOnly = true)
    {
        //FileSystem::createDir($basePath.'/'.$path);
        if ($file->isOk()) {
            if ($imageOnly && $file->isImage()) {
                $name = $path . $file->getSanitizedName();
                $file->move($basePath . '/' . $name);
                return $name;
            } else {
                $name = $path . $file->getSanitizedName();
                $file->move($basePath . '/' . $name);
                return $name;
            }
        }
        return null;
    }
}