<?php


namespace App\AdminModule\Presenters;


use App\AdminModule\Components\EmailStatus\IEmailStatusFormFactory;

class EmailStatusPresenter extends SettingPresenter
{
    /**
     * @var IEmailStatusFormFactory
     * @inject
     */
    public $emailStatusForm;

    public function createComponentEmailStatusForm()
    {
        $form = $this->emailStatusForm->create();
        $form->onDone[] = function () {
            $this->redirect('this');
        };
        return $form;
    }
}