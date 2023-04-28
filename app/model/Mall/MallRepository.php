<?php


namespace App\Model\Mall;


use App\Model\BaseRepository;
use Nette\Database\Context;

class MallRepository extends BaseRepository
{
    private $db;

    public function __construct(Context $db)
    {
        $this->db = $db;
    }

    public function getAll($lang)
    {
        return [
            '1' => 'Test1',
            '2' => 'Test2'
        ];
    }

}