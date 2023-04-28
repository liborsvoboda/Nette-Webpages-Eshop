<?php


namespace App\AdminModule\Components\Blog;


use App\Model\Blog\BlogRepository;
use App\Model\LocaleRepository;
use App\Model\Tag\TagRepository;
use App\Model\BlogCategory\BlogCategoryRepository;
use App\Model\Factory\FormFactory;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class BlogForm extends Control
{
    public $blogId = null,
        $onDone = [];

    private $blogRepository, $blogCategoryRepository, $formFactory, $tagRepository, $localeRepository;

    public function __construct(BlogRepository $blogRepository,
                                BlogCategoryRepository $blogCategoryRepository,
                                FormFactory $formFactory,
                                TagRepository $tagRepository,
                                LocaleRepository $localeRepository)
    {
        $this->blogRepository = $blogRepository;
        $this->blogCategoryRepository = $blogCategoryRepository;
        $this->tagRepository = $tagRepository;
        $this->localeRepository = $localeRepository;
        $this->formFactory = $formFactory;
    }

    public function setEdit($id)
    {
        $this->blogId = $id;
    }

    public function createComponentForm()
    {
        $form = $this->formFactory->create();
        $form->addUpload('image', 'form.image');
        $form->addCheckbox('active', 'form.active');
        $form->addSelect('blog_category_id', 'Kategória', $this->blogCategoryRepository->getForSelect());
//        $form->addMultiSelect('multiTag', 'Tagy', $this->tagRepository->getForSelect())->setHtmlAttribute('class', 'form-control, select2');
        $form->addSubmit('submit', 'Uložit');
        //$form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
//        $form->addMultiSelect('related_blogs', 'form.blog.related_blogs', $this->blogRepository->getForSelect());
//        $form->addMultiSelect('related_products', 'form.blog.related_products', $this->productRepository->getToRelatedSelect());
        $locales = $this->localeRepository->getAll();
        foreach ($locales as $locale) {
            $form->addContainer('locale' . $locale->id);
            $form['locale' . $locale->id]->addText('title', 'form.title');
            $form['locale' . $locale->id]->addTextArea('text', 'form.text')->setHtmlAttribute('class', 'editor');
            $form['locale' . $locale->id]->addText('slug', 'form.slug');
        }
        if ($this->blogId) {
            $values = $this->blogRepository->getByIdAdmin($this->blogId)->fetch();
            $form['active']->setDefaultValue($values->active);
//            $form['related_blogs']->setDefaultValue(json_decode($values->related_blogs, true));
//            $form['related_products']->setDefaultValue(json_decode($values->related_products, true));
            foreach ($locales as $locale) {
                $langItems = $this->blogRepository->getLangItems($this->blogId, $locale->lang->id)->fetch();
                @$form['locale' . $locale->id]['title']->setDefaultValue($langItems->title);
                @$form['locale' . $locale->id]['text']->setDefaultValue($langItems->text);
                @$form['locale' . $locale->id]['slug']->setDefaultValue($langItems->slug);
            }
        }
        $form->onSuccess[] = [$this, 'succesForm'];
        $form->onError[] = [$this, 'errorForm'];
        return $form;
    }

    public function succesForm(Form $form)
    {
        $values = $form->getValues();
        if($this->blogId) {
            $this->blogRepository->update($values, $this->blogId);
        } else {
            $this->blogRepository->add($values);
        }
        $this->onDone();
    }

    public function render()
    {
        if ($this->blogId) {
            $blog = $this->blogRepository->getByIdAdmin($this->blogId)->fetch();
            $this->template->image = $blog->image;
        }
        $this->template->locales = $this->localeRepository->getAll();
        $this->template->render(__DIR__.'/templates/blogForm.latte');
    }

}