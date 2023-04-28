<?php

namespace App\FrontModule\Components\Category;

use App\Model\Category\CategoryRepository;
use App\Model\Services\AppSettingsService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class CategorySearch extends Control
{

	/** @var string */
	private $search;

	/** @var int */
	private $limit = 10;

	/** @var CategoryRepository */
	private $categoryRepository;

	/** @var AppSettingsService */
	private $appSettingsService;

	public function __construct(
		string $search,
		CategoryRepository $categoryRepository,
		AppSettingsService $appSettingsService
	) {
		$this->search = $search;
		$this->categoryRepository = $categoryRepository;
		$this->appSettingsService = $appSettingsService;
	}

	public function setLimit(int $limit): self
	{
		$this->limit = $limit;
		return $this;
	}

	public function render()
    {
    	$this->template->categories = $this->categoryRepository->search($this->search, $this->limit);
        $this->template->render($this->appSettingsService->getComponentsTemplatesDir() . '/Category/CategoryFilter/categorySearch.latte');
    }
}