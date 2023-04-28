<?php


namespace App\Model\Import;

use Nette\Database\Connection;
use Nette\Database\Context;
use Nette\Database\UniqueConstraintViolationException;

class appImportService
{
    /**
     * @var Context
     */
    private Context $db;

    public function __construct(Context $db)
    {

        $this->db = $db;
    }

    private function getOldDb()
    {
        $olddb = new Connection('mysql:host=127.0.0.1;dbname=app1', 'root', '');
        return $olddb;
    }

    private $countries = [
        0 => 1,
        1 => 1,
        189 => 1,
        56 => 2,
        220 => 232
    ];

    private $groups = [
        1 => 1,
        13 => 2,
        11 => 3,
        9 => 4,
        12 => 5,
        14 => 6,
        15 => 7,
        16 => 8
    ];


    public function importUsers()
    {
        $oldDb = $this->getOldDb();
        $oldUsers = $oldDb->query('SELECT * FROM oc_customer');
        //dumpe($oldUsers->fetchAll());
        foreach ($oldUsers as $oldUser) {
            $user = [
                'id' => $oldUser->customer_id,
                'parent_id' => null,
                'ref_no' => $oldUser->ref_no,
                'parent_ref_no' => $oldUser->parent_ref_no,
                'firstName' => $oldUser->firstname,
                'lastName' => $oldUser->lastname,
                'email' => $oldUser->email,
                'phone' => $oldUser->telephone,
                'oldpassword' => $oldUser->password,
                'oldsalt' => $oldUser->salt,
                'password' => null,
                'registered_at' => $oldUser->date_added,
                'autologin' => $oldUser->autologin,
                'user_level_id' => $this->groups[$oldUser->customer_group_id]
            ];
            @$customf = unserialize((string)$oldUser->custom_field);
            if(isset($customf[11]) && strlen($customf[11]) > 0) {
                if($customf[11] != $user['parent_ref_no']) {
                    $user['parent_ref_no'] = $customf[11];
                }
            }
            $address = $oldDb->fetch('SELECT * FROM oc_address WHERE customer_id = ?', $oldUser->customer_id);
            if($address) {
                $user['street'] = $address->address_1;
                $user['city'] = $address->city;
                $user['zip'] = $address->postcode;
                $user['country_id'] = $this->countries[$address->country_id];
                $custom = unserialize($address->custom_field);
                if(isset($custom[5]) && strlen($custom[5])) $user['companyName'] = $custom[5];
                if(isset($custom[6]) && strlen($custom[6])) $user['ico'] = strlen($custom[6]) > 15 ? '' : $custom[6];
                if(isset($custom[7]) && strlen($custom[7])) $user['dic'] = strlen($custom[7]) > 20 ? '' : $custom[7];
                if(isset($custom[8]) && strlen($custom[8])) $user['icdph'] = strlen($custom[8]) > 20 ? '' : $custom[8];
                if(isset($custom[12]) && strlen($custom[12])) $user['iban'] = $custom[12];
            }
            try {
                $this->db->table('user')->insert($user);
            } catch (UniqueConstraintViolationException $exception)
            {
                $id = $user['id'];
                unset($user['id']);
                $this->db->table('user')->where('id', $id)->update($user);
            }

        }
    }

    public function updateParents()
    {
        $users = $this->db->table('user');
        foreach ($users as $user) {
            $ref = $this->db->table('user')->where('ref_no', $user->parent_ref_no)->fetch();
            if($ref) {
                $this->db->table('user')->where('id', $user->id)->update(['referral_id' => $ref->id]);
            }
        }
    }
}