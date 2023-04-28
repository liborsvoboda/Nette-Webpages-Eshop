<?php


namespace App\AdminModule\Components\Attribute;


use App\Model\Attribute\AttributeRepository;
use App\Model\Attribute\AttributeValueRepository;
use App\Model\Factory\GridFactory;
use App\Model\LocaleRepository;
use Nette\Application\UI\Control;
use Nette\Forms\Container;
use Nette\Utils\ArrayHash;

class AttributeValueGrid extends Control
{

    private $attributeRepository, $localeRepository, $gridFactory, $attributeId, $attributeValueRepository;

    public function __construct(int $attributeId,
                                AttributeRepository $attributeRepository,
                                LocaleRepository $localeRepository,
                                GridFactory $gridFactory,
                                AttributeValueRepository $attributeValueRepository)
    {
        $this->attributeRepository = $attributeRepository;
        $this->attributeValueRepository = $attributeValueRepository;
        $this->localeRepository = $localeRepository;
        $this->gridFactory = $gridFactory;
        $this->attributeId = $attributeId;
    }

    public function createComponentGrid()
    {
        $grid = $this->gridFactory->create();
        $grid->setDataSource($this->getDataSource());;
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $grid->addColumnText('locale' . $locale->id, $locale->country->name)
                ->setRenderer(function ($row) use ($locale) {
                    return $this->attributeValueRepository->getValue($row->id, $locale->id);
                });
        }
        $grid->addInlineEdit()
            ->onControlAdd[] = function (Container $container) use ($locales) {
            foreach ($locales as $locale) {
                $container->addText('locale' . $locale->id, '');
            }
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, $row) use ($locales): void {
            foreach ($locales as $locale) {
                $container['locale' . $locale->id]->setDefaultValue($this->attributeValueRepository->getValue($row->id, $locale->lang_id));
            }
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'updateValue'];
        $grid->addInlineAdd()
            ->onControlAdd[] = function (Container $container) use ($locales) {
            foreach ($locales as $locale) {
                $container->addText('locale' . $locale->id, '');
            }
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'addValue'];
        $grid->getInlineEdit()->onCustomRedraw[] = function() use ($grid): void {
            $grid->redrawControl();
        };
        $grid->getInlineAdd()->onCustomRedraw[] = function() use ($grid): void {
            $grid->redrawControl();
        };
        return $grid;
    }

    public function updateValue($id, $values)
    {
        $this->attributeValueRepository->update($id, $values);
        $this->redrawControl('attributeValueGrid');
        $this['grid']->redrawControl();
    }

    public function addValue($values)
    {
        $this->attributeValueRepository->add($this->attributeId, $values);
        $this->redrawControl('attributeValueGrid');
    }

    private function getDataSource()
    {
        return $this->attributeValueRepository->getByAttributeId($this->attributeId);
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/attributeValueGrid.latte');
    }
}