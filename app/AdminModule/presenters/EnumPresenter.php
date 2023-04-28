<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Setting\IEnumFormFactory;

class EnumPresenter extends SettingPresenter
{
    /**
     * @var IEnumFormFactory
     * @inject
     */
    public $enumForm;

    public function createComponentEnumForm()
    {
        $form = $this->enumForm->create();
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }

}