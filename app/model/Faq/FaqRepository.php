<?php


namespace App\Model\Faq;


use App\Model\BaseRepository;
use Nette\Database\Context;
use Nette\Utils\DateTime;

class FaqRepository extends BaseRepository
{
    /**
     * @var Context
     */
    private Context $db;

    public function __construct(Context $db)
    {
        $this->db = $db;
    }

    public function getAll($langId = null)
    {
        $faq = $this->db->table('faq');
        if($langId) {
            $faq->where('locale_id', $langId);
        }
        return $faq;
    }

    public function getById($id)
    {
        return $this->getAll()->where('id', $id);
    }

    public function add($values)
    {
        dumpe($values);
        $values->timestamp = new DateTime();
        $this->getAll()->insert($values);
    }

    public function update($id, $values)
    {
        $this->getById($id)->update($values);
    }

    public function remove($id)
    {
        $this->getById($id)->delete();
    }
}