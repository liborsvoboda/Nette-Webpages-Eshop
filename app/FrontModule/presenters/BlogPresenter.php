<?php


namespace App\FrontModule\Presenters;


use App\Model\Blog\BlogRepository;
use Nette\Application\BadRequestException;

class BlogPresenter extends BasePresenter
{
    /**
     * @var BlogRepository
     * @inject
     */
    public $blogRepository;

    public function actionDefault()
    {
        $blogs = $this->blogRepository->getAll($this->langId, true)->order('blog.timestamp DESC');
        $this->template->blogs = $blogs;
    }

    public function actionPost($slug)
    {
        $blog = $this->blogRepository->getById($slug);
        if(!$blog) {
            throw new BadRequestException();
        }
        $this->template->blog = $blog->fetch();
    }
}