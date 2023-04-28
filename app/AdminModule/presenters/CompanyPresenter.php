<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\Setting\ICompanyFormFactory;

class CompanyPresenter extends SettingPresenter
{
    /**
     * @var ICompanyFormFactory
     * @inject
     */
    public $companyForm;


    public function createComponentCompanyForm()
    {
        $form = $this->companyForm->create();
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }

}