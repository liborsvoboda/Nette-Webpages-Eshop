<?php


namespace App\AdminModule\Presenters;


class OverviewPresenter extends AdminPresenter
{
    public function beforeRender()
    {
        parent::beforeRender();
        $this->sideMenu = [
            [
                'action' => 'Order:default',
                'label' => 'Objednávky'
            ],
            [
                'action' => 'Customer:default',
                'label' => 'Zákazníci'
            ],
            [
                'action' => 'UserLevel:default',
                'label' => 'Zákaznické skupiny'
            ],
        ];
        $this->template->sideMenu = $this->sideMenu;
    }
}