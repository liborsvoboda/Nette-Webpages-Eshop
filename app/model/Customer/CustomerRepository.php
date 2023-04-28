<?php


namespace App\Model\Customer;


use App\Model\BaseRepository;
use App\Model\Services\UserManager;
use Nette\Database\Context;

class CustomerRepository extends BaseRepository
{
    private $db, $table = 'user', $subIds;

    public function __construct(Context $db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getById($customerId)
    {
        return $this->getAll()->where('id', $customerId);
    }

    public function setActive($id, $value)
    {
        $this->getAll()->where('id', $id)->update(['active' => $value]);
    }

    public function getChildren($parentId = null, $noCustomer = false)
    {
        $children = $this->getAll()->where('referral_id', $parentId);
        if($noCustomer === true) {
            $children->where('user_level_id >= 1');
        }
        return $children;
    }

    public function getSubIds($parentId)
    {
        $result = $this->getChildren($parentId)->fetchAll();
        if (!$result) {
            return $parentId;
        }
        foreach ($result as $mainCategory) {
            $category = array();
            $this->subIds = $this->subIds . $mainCategory->id . ',';
//            $category['name'] = $mainCategory->name;
//            $category['parent_id'] = $mainCategory->parent_id;
            $category['sub_categories'] = $this->getSubIds($mainCategory->id);
        }
        return substr($this->subIds, 0, -1);
    }

    public function getSubIdsArray($parentId, $withSelf = false)
    {
        $subs = $this->getSubIds($parentId);
        $subsArray = explode(',', $subs);
        $out = $this->getById($subsArray);
        $out->select('*, CONCAT(ref_no, " - ", firstName, " ", lastName, " (", email ,")") usr')->where('active = 1');
        $out = $out->fetchPairs('id', 'usr');
        if($withSelf) {
            $self = $this->getById($parentId)->select('*, CONCAT(ref_no, " - ", firstName, " ", lastName, " (", email ,")") usr')->where('active = 1')->fetchPairs('id', 'usr');
            $out = $self + $out;
        }
        return $out;
    }

    public function nullSubIds()
    {
        $this->subIds = null;
    }

    public function getCustomerLevel($customerId)
    {
        $customer = $this->getById($customerId)->fetch();
        return $customer ? $customer->user_level_id : null;
    }
}