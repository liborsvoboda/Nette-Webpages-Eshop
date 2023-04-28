<?php


namespace App\AdminModule\Presenters;


class ContentPresenter extends AdminPresenter
{
    public function beforeRender()
    {
        parent::beforeRender();
        $this->sideMenu = [
            [
                'action' => 'Page:default',
                'label' => 'Stránky'
            ],
            [
                'action' => 'Blog:default',
                'label' => 'Články'
            ],
            [
                'action' => 'Menu:default',
                'label' => 'Menu'
            ],
            [
                'action' => 'Slider:default',
                'label' => 'Slider'
            ],
            [
                'action' => 'Slider:banner',
                'label' => 'Bannery'
            ],
            [
                'action' => 'Gallery:default',
                'label' => 'Galerie'
            ],

        ];
        $this->template->sideMenu = $this->sideMenu;
    }
}