<?php

namespace App\Model\Tag;

use App\Model\BaseRepository;
use Nette\Database\Context;
use Nette\Database\Table\Selection;

class TagRepository extends BaseRepository {

    private $table = 'tag';
    protected $db;

    public function __construct(Context $db) {
        $this->db = $db;
    }

    public function getAll() {

        return $this->db->table($this->table)
                        ->select(':tag_lang.*, tag.*')
                        ->where(':tag_lang.lang_id', $this->langId());
    }

    public function getById($id): Selection {
        return $this->getAll()
                        ->where('tag.id', $id);
    }

    public function add($values) {
        $tagId = $this->db->table($this->table)->insert(['active' => $values->active]);
        unset($values->active);
        $values->tag_id = $tagId;
        $values->lang_id = $this->langId();
        if (strlen($values->slug) < 1) {
            $values->slug = Strings::webalize($values->title);
        }
        $this->db->table('tag_lang')->insert($values);
    }

    public function update($values, $tagId) {
        $data = ['active' => $values->active];
        $this->db->table($this->table)->where('id', $tagId)->update($data);
        unset($values->active);
        if (strlen($values->slug) < 1) {
            $values->slug = Strings::webalize($values->title);
        }
        $this->db->table('tag_lang')->where('tag_id', $tagId)->where('lang_id', $this->langId())->update($values);
    }

    public function getForSelect() {
        $blogCategory = $this->getAll();

        $out = $blogCategory->fetchPairs('id', 'title');
        return $out;
    }
    
    public function saveTags($blogId,$tags){
         $this->db->table('blog_tag')->where('blog_id', $blogId)->delete();
        if($tags){
            foreach ($tags as $tag) {
                $this->db->table('blog_tag')->insert(['blog_id' => $blogId, 'tag_id'=>$tag]);
            }
        }
    }
    
    public function getMultiTag(int $blogId) : array
    {
        return $this->db->table('blog_tag')->where('blog_id', $blogId)
            ->fetchPairs('tag_id', 'tag_id');
    }

}
