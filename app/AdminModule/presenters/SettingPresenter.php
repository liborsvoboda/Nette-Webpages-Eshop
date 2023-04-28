<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Setting\ICompanyFormFactory;
use App\AdminModule\Components\Setting\IEnumFormFactory;


abstract class SettingPresenter extends AdminPresenter
{
    public function beforeRender()
    {
        parent::beforeRender();
        $this->sideMenu = [
            [
                'action' => 'Company:default',
                'label' => 'Firemní údaje'
            ],
            [
                'action' => 'Enum:default',
                'label' => 'Číselné řady'
            ],
            [
                'action' => 'Shipping:default',
                'label' => 'Doprava'
            ],
            [
                'action' => 'Payment:default',
                'label' => 'Platební metody'
            ],
            [
                'action' => 'EmailStatus:default',
                'label' => 'Emaily stavů'
            ],
            [
                'action' => 'Locale:default',
                'label' => 'Jazyky/měny'
            ]
        ];
        $this->template->sideMenu = $this->sideMenu;
    }

}