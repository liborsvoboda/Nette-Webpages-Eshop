<?php

namespace App\Model\BlogCategory;

use App\Model\BaseRepository;
use Nette\Database\Context;
use Nette\Database\Table\Selection;

class BlogCategoryRepository extends BaseRepository {
    
    private $table = 'blog_category';
    
   protected $db;

    public function __construct(Context $db)
    {
        $this->db = $db;
    }
    
    public function getAll(){
       
         return $this->db->table($this->table)
            ->select('blog_category.*, :blog_category_lang.*');
            //->where(':blog_category_lang.lang_id', $this->langId());
    }
    
    public function getById($id) : Selection
    {
        return $this->getAll()
            ->where('blog_category.id', $id);
    }
    
    public function add($values){
        $categoryId = $this->db->table($this->table)->insert(['visible' => $values->visible]);
        unset($values->visible);
        $values->category_id = $categoryId;
        $values->lang_id = $this->langId();
        if(strlen($values->slug) < 1) {
            $values->slug = Strings::webalize($values->name);
        }
        $this->db->table('blog_category_lang')->insert($values);
    }
    
    public function update($values, $blogCategoryId)
    {
        $active = ['visible' => $values->visible];
        $this->db->table($this->table)->where('id', $blogCategoryId)->update($active);
        unset($values->visible);
        if(strlen($values->slug) < 1) {
            $values->slug = Strings::webalize($values->title);
        }
        $this->db->table('blog_category_lang')->where('category_id', $blogCategoryId)->where('lang_id', $this->langId())->update($values);
    }
    
    
    public function getForSelect() {
        $blogCategory = $this->getAll();

        $out = $blogCategory->fetchPairs('id', 'name');
        return $out;
    }
}
