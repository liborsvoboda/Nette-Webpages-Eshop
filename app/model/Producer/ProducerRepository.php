<?php


namespace App\Model\Producer;


use App\Model\BaseRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\UploadService;
use Nette\Database\Context;
use Nette\Utils\Strings;


class ProducerRepository extends BaseRepository
{
    private $table = 'producer';

    protected $db, $appSettingsService;

    public function __construct(Context $db, AppSettingsService $appSettingsService)
    {
        $this->db = $db;
        $this->appSettingsService = $appSettingsService;
    }

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getRandom($limit = 5)
    {
        return $this->getAll()
            ->order('RAND()')
            ->limit($limit);
    }

    public function getForSelect($producersArray = null)
    {
        $producers = $this->getAll();
        if ($producersArray) {
            $producersArray = array_filter($producersArray);
            $producers->where('id', $producersArray);
        }
        $out = $producers->order('name')->fetchPairs('id','name');
        return $out;
    }

    public function add($values)
    {
        if($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/producer/');
        }

        return $this->db->table($this->table)->insert($values);
    }

    public function update($values, $producerId)
    {
        $update = [
            'name' => $values->name,
            'description' => $values->description
        ];
        if($values->image) {
            $values->image = UploadService::upload($values->image, $this->appSettingsService->getWwwDir(), '/upload/images/producer/');
        }
        if($values->image) {
            $update['image'] = $values->image;
        }
        $this->db->table($this->table)->where('id', $producerId)->update($values);
    }

    public function getIdByName($name)
    {
        $producer = $this->getAll()->where('name', $name)->fetch();
        return $producer ? $producer->id : null;
    }

    public function getIdBySlug($slug)
    {
        $producer = $this->getAll()->where('slug', $slug)->fetch();
        return $producer ? $producer->id : null;
    }

    public function getSlugById($id)
    {
        $slug = $this->getAll()->where('id', $id)->fetch();
        return $slug ?$slug->slug : null;
    }

    public function slugToId($slug)
    {
        return $this->getIdBySlug($slug);
    }

    public function idToSlug($id)
    {
        return $this->getSlugById($id);
    }

    public function getById($id)
    {
        return $this->getAll()->where('id', $id);
    }

    public function makeLinks()
    {
        $items = $this->getAll();
        foreach ($items as $item) {
            $update = [
                'slug' => Strings::webalize($item->name)
            ];
            $this->db->table($this->table)->where('id', $item->id)->update($update);
        }
    }

}