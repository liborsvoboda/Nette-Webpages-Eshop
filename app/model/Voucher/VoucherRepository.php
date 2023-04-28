<?php


namespace App\Model\Voucher;


use App\Model\BaseRepository;
use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Http\Session;
use Nette\Security\User;
use Tracy\Debugger;
use App\Model\User\UserRepository;

class VoucherRepository extends BaseRepository
{

    const TYPE_PERCENT = 1,
        TYPE_PRICE = 2;

    const VOCHER_TYPE = [
        self::TYPE_PERCENT => 'Sleva v %',
        self::TYPE_PRICE => 'Sleva v hodnotě'
    ];

    private $db, $session, $section, $user, $table = 'voucher', $userRepository;

    public function __construct(Context $db, User $user, Session $session, UserRepository $userRepository)
    {
        $this->user = $user;
        $this->db = $db;
        $this->session = $session;
        $this->section = $session->getSection('voucher');
        $this->userRepository = $userRepository;
    }

    public function getAll($langId = null)
    {
        $langId = $langId ?? $this->langId();
        return $this->db->table($this->table)->where('lang_id', $langId);
    }

    public function getAllLang($langId = null)
    {
        $langId = $langId ?? $this->langId();
        return $this->db->table($this->table);//->where('lang_id', $langId);
    }

    public function getById($id, $langId = null): Selection
    {
        $langId = $langId ?? $this->langId();
        return $this->getAll($langId)->where('id', $id);
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

    public function unsetVoucher()
    {
        unset($this->section->voucher);
    }

    public function getDiscount($price)
    {
        $discount = null;
        $voucher = $this->section->voucher;
        if (!$voucher) {
            return 0;
        }
        $type = $voucher['type'];
        $value = $voucher['value'];
        switch ($type) {
            case self::TYPE_PERCENT :
                $discount = round(($value / 100) * $price, 2);
                break;
            case self::TYPE_PRICE :
                $discount = round($value, 2);
                break;
        }
        return $discount;
    }

    public function getVoucherCode()
    {
        $voucher = $this->section->voucher;
        return $voucher['code'] ?? null;
    }

    public function getVoucherId()
    {
        $voucher = $this->section->voucher;
        return $voucher['id'] ?? null;
    }

    public function getVoucherType()
    {
        $voucher = $this->section->voucher;
        return $voucher['type'];
    }

    public function saveVoucher($code)
    {
        $test = $this->getAll()->where('code', $code)->fetch();
        $orderData = $this->session->getSection('orderData');

        if (!$this->user->isLoggedIn()){
            $orderData['tmp_user_level_id'] = 1;
        }

        if (!$test) {
            return false;
        } 

        if (isset($test->parent_ref_no)) { 
            if($this->user->isLoggedIn()){
                $auser = $this->userRepository->getById($this->user->getId())->fetch();
                if (isset($auser->parent_ref_no)){
                    if ($test->parent_ref_no != $auser->parent_ref_no) {unset($this->section->voucher); return false;}
                } else {unset($this->section->voucher);return false;}
            } else {unset($this->section->voucher);return false;}
        }

        if (isset($test->max_user_group_id)){
            if (isset($orderData['tmp_user_level_id'])){
                if ($orderData['tmp_user_level_id'] > $test->max_user_group_id) {unset($this->section->voucher); return false;}
            } else {return false;}
        }

        $this->section->voucher = [
            'id' => $test->id,
            'code' => $code,
            'type' => $test->type,
            'value' => $test->value
        ];
        return true;
    }
}
