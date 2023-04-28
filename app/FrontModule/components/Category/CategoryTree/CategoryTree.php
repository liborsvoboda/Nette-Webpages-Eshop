<?php

namespace App\FrontModule\Components\Category;

use App\Model\Category\CategoryRepository;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;

class CategoryTree extends Control
{

    /** @var int|null */
    private $categoryId;

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var AppSettingsService */
    private $appSettingsService;

    public function __construct(
        int $categoryId = null,
        CategoryRepository $categoryRepository,
        AppSettingsService $appSettingsService
    ) {
        $this->categoryId = $categoryId;
        $this->categoryRepository = $categoryRepository;
        $this->appSettingsService = $appSettingsService;
    }

    public function render()
    {
        $parents = [$this->categoryId];
        $rows = $this->categoryRepository->getParents($this->categoryId);
        foreach ($rows as $row) {
            $parents[] = $row->id;
        }

        $this->template->tree = $this->categoryRepository->getCategoryTree(null);
        $this->template->currentTree = $parents;
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir().'/Category/CategoryTree/categoryTree.latte');
    }
}