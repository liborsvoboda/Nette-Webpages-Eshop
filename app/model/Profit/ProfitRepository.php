<?php


namespace App\Model\Profit;


use App\Model\BaseRepository;
use Nette\Database\Context;

class ProfitRepository extends BaseRepository
{

    private $db, $table = 'profit';

    public function __construct(Context $db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getById($id)
    {
        return $this->getAll()->where('id', $id);
    }

    public function add($values)
    {
        $this->db->table($this->table)->insert($values);
    }

    public function update($id, $values)
    {
        $this->db->table($this->table)->where('id', $id)->update($values);
    }

    public function remove($id)
    {
        $this->db->table($this->table)->where('id', $id)->delete();
    }
}