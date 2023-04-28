<?php


namespace App\FrontModule\Presenters;


use App\Model\Faq\FaqRepository;
use App\Model\Page\PageRepository;
use App\FrontModule\Components\ContactForm\IContactFormFactory;
use Nette\Application\BadRequestException;

class PagePresenter extends BasePresenter
{
    /**
     * @var PageRepository
     * @inject
     */
    public $pageRepository;

    /**
     * @var FaqRepository
     * @inject
     */
    public $faqRepository;

	/**
     * @var IContactFormFactory
     * @inject
     */
    public $contactFormFactory;

    private $pageId;

    public function actionDefault($slug)
    {
        if(!$this->getParameter('slug')) {
            throw new BadRequestException();
        }
        $this->pageId = $this->getParameter('slug');
        $page = $this->pageRepository->getById($this->pageId)->fetch();
        $this->template->page = $page;
    }

    public function renderFaq()
    {
        $this->template->faqs = $this->faqRepository->getAll($this->langId);
    }

	public function createComponentContactForm()
    {
        return $this->contactFormFactory->create();
    }
}