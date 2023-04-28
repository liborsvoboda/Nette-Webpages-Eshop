<?php

namespace App\Model\Blog;

use App\Model\BaseRepository;
use App\Model\LocaleRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\UploadService;
use App\Model\Tag\TagRepository;
use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Utils\Strings;

class BlogRepository extends BaseRepository {

    private $db, $table = 'blog', $tagRepository, $appSettingsService, $localeRepository;

    public function __construct(Context $db, TagRepository $tagRepository, AppSettingsService $appSettingsService, LocaleRepository $localeRepository) {
        $this->db = $db;
        $this->tagRepository = $tagRepository;
        $this->appSettingsService = $appSettingsService;
        $this->localeRepository = $localeRepository;
    }

    public function getAll($langId = null, bool $activeOnly = false) {
        $langId = $langId ?? $this->langId();
        $select = $this->db->table($this->table)
				->select(':blog_lang.* ,blog.*')
				->where(':blog_lang.lang_id', $langId);
		if ($activeOnly) $select->where('blog.active', 1);
		return $select;
    }

    public function getAllAdmin() {
        $select = $this->db->table($this->table)
				->select(':blog_lang.* ,blog.*')
                ->where('blog.id > 10');
		return $select;
    }

    public function getById($id, $langId = null): Selection {
        return $this->getAll($langId)
                        ->where('blog.id', $id);
    }

    public function getByIdAdmin($id): Selection {
        return $this->getAllAdmin()
            ->where('blog.id', $id);
    }

    public function getBySlug($slug): Selection {
        return $this->getAll()
                        ->where(':blog_lang.slug', $slug);
    }

    public function slugToId($slug) {
        $blog = $this->getBySlug($slug)->fetch();
        return $blog ? $blog->id : null;
    }

    public function idToSlug($id) {
        $blog = $this->getById($id)->fetch();
        return $blog ? $blog->slug : null;
    }

    public function add($values) {
        $blog = [
            'active' => $values->active,
            'blog_category_id' => $values->blog_category_id
        ];
        if ($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/category/');
        }
        if ($values->image) {
            $blog['image'] = $values->image;
        }
        $blogId = $this->db->table($this->table)->insert($blog);
        unset($values->active);
        unset($values->blog_category_id);
        unset($values->image);
        if ($values->multiTag) {
            $this->tagRepository->saveTags($blogId, $values->multiTag);
        }
        unset($values->multiTag);
        $values->blog_id = $blogId;
        $values->lang_id = $this->langId();
        if (strlen($values->slug) < 1) {
            $values->slug = Strings::webalize($values->title);
        }
        $this->db->table('blog_lang')->insert($values);
    }

    public function remove($blogId) {
        $this->db->table('blog_lang')->where('blog_id', $blogId)->delete();
        $this->db->table($this->table)->where('id', $blogId)->delete();
    }

    public function update($values, $blogId) {
        $blog = [
            'active' => $values->active,
            'blog_category_id' => $values->blog_category_id
        ];
        if ($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/category/');
        }
        if ($values->image) {
            $blog['image'] = $values->image;
        }
        $this->db->table($this->table)->where('id', $blogId)->update($blog);
        unset($values->active);
        unset($values->blog_category_id);
        unset($values->image);
        /*
        if ($values->multiTag) {
            $this->tagRepository->saveTags($blogId, $values->multiTag);

        unset($values->multiTag);
        */
        $this->saveLangValues($values, $blogId);
    }

    private function saveLangValues($values, $blogId)
    {
        $locales = $this->localeRepository->getAll();
        $this->db->table('blog_lang')->where('blog_id', $blogId)->delete();
        foreach ($locales as $locale) {
            if (strlen($values['locale' . $locale->id]->slug) < 1) {
                $values['locale' . $locale->id]->slug = Strings::webalize($values['locale' . $locale->id]->title);
            }
            $blogLang = [
                'blog_id' => $blogId,
                'lang_id' => $locale->lang->id,
                'title' => $values['locale' . $locale->id]->title,
                'text' => $values['locale' . $locale->id]->text,
                'slug' => $values['locale' . $locale->id]->slug,
            ];
            $this->db->table('blog_lang')->insert($blogLang);
        }
    }

    public function getLangItems($blogId, $langId)
    {
        return $this->getAll($langId)->where(':blog_lang.blog_id', $blogId);
    }

}
