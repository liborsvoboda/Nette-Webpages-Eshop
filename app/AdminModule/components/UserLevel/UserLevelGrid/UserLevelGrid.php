<?php


namespace App\AdminModule\Components\UserLevel;


use App\Model\Control\BaseControl;
use App\Model\Factory\FormFactory;
use App\Model\Factory\GridFactory;
use App\Model\LocaleRepository;
use App\Model\UserLevel\UserLevelRepository;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class UserLevelGrid extends BaseControl
{
    private $userLevelRepository, $gridFactory, $formFactory, $localeRepository;

    public $onEdit = [], $onRedirect = [];

    public function __construct(UserLevelRepository $userLevelRepository, GridFactory $gridFactory, FormFactory $formFactory, LocaleRepository $localeRepository)
    {
        $this->userLevelRepository = $userLevelRepository;
        $this->gridFactory = $gridFactory;
        $this->formFactory = $formFactory;
        $this->localeRepository = $localeRepository;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('ord', 'Poradie')
            ->setEditableCallback([$this, 'changeOrder'])
            ->setEditableInputType('text',['class' => 'form-control']);
        $grid->addColumnText('name', 'form.title');
        $grid->addColumnText('min_turnover', 'form.user.min_turnover');
        $grid->addColumnText('min_turnover_cz', 'form.user.min_turnover_cz');
        $grid->addColumnText('commission', 'Provízia')
            ->setRenderer(function ($row){
                return (int)$row->commission.'%';
            });
        $grid->addColumnText('user_group.name', 'form.user.group');
        $grid->addAction('edit', '', 'Edit', ['id'])
            ->setIcon('edit');
        $grid->addAction('remove', '', 'remove', ['id'])
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu smazat?')
            );
        return $grid;
    }

    public function changeOrder($id, $value)
    {
        $this->userLevelRepository->setValue($id, 'ord', $value);
    }

    public function handleEdit($id)
    {
        $this->onEdit($id);
    }

    public function handleRemove($id)
    {
        $status = $this->userLevelRepository->remove($id);
        if($status === true) {
            $message = ['message' => 'Odstranené','type' => 'success'];
        } else {
            $message = ['message' => 'Nejde odstrániť','type' => 'danger'];
        }
        $this->onRedirect($message);
    }

    private function getDataSource()
    {
        return $this->userLevelRepository->getAll()->order('ord DESC');
    }


    public function render()
    {
        $this->template->render(__DIR__.'/template/userLevelGrid.latte');
    }
}