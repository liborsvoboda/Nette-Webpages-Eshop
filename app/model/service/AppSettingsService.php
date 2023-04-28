<?php


namespace App\Model\Services;


class AppSettingsService
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getWwwDir()
    {
        return $this->config['wwwDir'];
    }

    public function getAppDir()
    {
        return $this->config['appDir'];
    }

    public function getComponentsTemplatesDir()
    {
        return $this->config['componentsTemplatesDir'];
    }

    public function getEmailTemplatesDir()
    {
        return $this->config['emailTemplatesDir'];
    }

    public function getPdfTemplatesDir()
    {
        return $this->config['pdfTemplatesDir'];
    }

    public function isDistAssets()
    {
        return $this->config['distAssets'];
    }

}