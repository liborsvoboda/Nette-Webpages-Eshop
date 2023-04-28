<?php


namespace App\AdminModule\Presenters;


abstract class CataloguePresenter extends AdminPresenter
{

    public function beforeRender()
    {
        parent::beforeRender();
        $this->sideMenu = [
            [
                'action' => 'Product:default',
                'label' => 'Produkty'
            ],
            [
                'action' => 'ProductCategory:default',
                'label' => 'Kategorie'
            ],
            [
                'action' => 'Producer:default',
                'label' => 'Výrobci'
            ],
            [
                'action' => 'ProductAttribute:default',
                'label' => 'Atributy'
            ],
            [
                'action' => 'Profit:default',
                'label' => 'Marže'
            ],
            [
                'action' => 'ProductReview:default',
                'label' => 'Recenze'
            ],
            [
                'action' => 'Voucher:default',
                'label' => 'Slevové kupóny'
            ],
            [
                'action' => 'Product:featured',
                'label' => 'Produkty na HP'
            ]
            
        ];
        $this->template->sideMenu = $this->sideMenu;
    }

}
