<?php


namespace App\AdminModule\Components\Shipping;


use App\Model\Factory\GridFactory;
use App\Model\Shipping\ShippingRepository;
use Nette\Application\UI\Control;
use Nette\Utils\ArrayHash;

class ShippingLevelGrid extends Control
{

    private $shippingRepository, $gridFactory, $shippingId;

    public function __construct(ShippingRepository $shippingRepository, GridFactory $gridFactory)
    {
        $this->shippingRepository = $shippingRepository;
        $this->gridFactory = $gridFactory;
    }

    public function setShippingId($shippingId)
    {
        $this->shippingId = $shippingId;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());
        $grid->addColumnText('from', 'Od');
        $grid->addColumnText('to', 'Do');
        $grid->addColumnText('price', 'Cena');
        $grid->addInlineAdd()
            ->onControlAdd[] = function(\Nette\Forms\Container $container) {
            $container->addText('from', 'Od');
            $container->addText('to', 'Do');
            $container->addText('price', 'Cena');
        };
        $grid->getInlineAdd()->onSubmit[] = function ($data) {
            $this->add($data);
        };
        $grid->addInlineEdit()
            ->onControlAdd[] = function(\Nette\Forms\Container $container): void {
            $container->addText('from', '');
            $container->addText('to', '');
            $container->addText('price', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function(\Nette\Forms\Container $container, $item): void {
            $container->setDefaults([
                'from' => $item->from,
                'to' => $item->to,
                'price' => $item->price
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = function ($id, $data) {
            $this->edit($id, $data);
        };

        return $grid;
    }

    public function add($data)
    {
        $this->shippingRepository->addLevel($this->shippingId, $data->from, $data->to, $data->price);
        $this['grid']->redrawControl();
    }

    public function edit($id, $data)
    {
        $this->shippingRepository->updateLevel($id, $data->from, $data->to, $data->price);
        $this['grid']->redrawControl();
    }

    private function getDataSource()
    {
        return $this->shippingRepository->getLevels($this->shippingId);
    }

    public function render()
    {
        $this->template->render(__DIR__.'/templates/shippingLevelGrid.latte');
    }
}