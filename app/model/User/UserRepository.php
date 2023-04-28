<?php


namespace App\Model\User;


use App\Model\BaseRepository;
use App\Model\Email\EmailService;
use App\Model\Order\OrderRepository;
use App\Model\Services\UserManager;
use Nette\Database\Context;
use Nette\Http\Session;
use Nette\Security\AuthenticationException;
use Nette\Security\Identity;
use Nette\Security\IUserStorage;
use Nette\Security\Passwords;
use Nette\Security\User;
use Nette\Utils\DateTime;
use Nette\Utils\Random;


class UserRepository extends BaseRepository
{

    private $user, $db, $table = 'user', $passwords, $emailService, $session, $section, $storage;

    public function __construct(User $user, Context $db, Passwords $passwords, EmailService $emailService, Session $session, IUserStorage $storage)
    {
        $this->user = $user;
        $this->db = $db;
        $this->passwords = $passwords;
        $this->emailService = $emailService;
        $this->session = $session;
        $this->section = $session->getSection('orderData');
        $this->storage = $storage;
    }

    public function createUserFromOrder()
    {
        $orderData = $this->section->orderData;
        $password = Random::generate();

        $parent_ref = $this->session->getSection('referral');

        $new = [
            'role'			=> UserManager::USER_CUSTOMER,
            'firstName' 	=> $orderData['firstName'],
            'lastName' 		=> $orderData['lastName'],
            'email' 		=> $orderData['email'],
            'street' 		=> $orderData['street'],
            'city' 			=> $orderData['city'],
            'zip' 			=> $orderData['zip'],
            'phone' 		=> $orderData['phone'],
            'parent_ref_no' => ((bool)$parent_ref->ref_no) ? $parent_ref->ref_no : null,
            'user_group_id' => (isset($orderData['user_group_id'])) ? $orderData['user_group_id'] : 1,
            'user_level_id' => (isset($orderData['user_group_id'])) ? $orderData['user_group_id'] : 1,
            'password'      => $this->passwords->hash($password)
        ];
        $this->db->table($this->table)->insert($new);
        $this->user->login($new['email'], $password);
    }

    public function add($email, $password)
    {
        $test = $this->getAll()->where('email', $email)->fetch();
        if($test) {
            throw new AuthenticationException('Email je už registrovaný');
        }
        $hash = $this->passwords->hash($password);
        $this->db->table($this->table)->insert(['email' => $email, 'password' => $hash, 'role' => 3]);
        return $hash;
    }

    public function copyInfoToSession($userId = null)
    { 
        $userId = $userId ?? $this->user->getId();
        $aUser = $this->getById($userId)->fetch();

        $parent_ref = $this->session->getSection('referral');

        $this->section->orderData = [
            'firstName' 	=> $aUser['firstName'],
            'lastName' 		=> $aUser['lastName'],
            'email' 		=> $aUser['email'],
            'street' 		=> $aUser['street'],
            'city' 			=> $aUser['city'],
            'zip' 			=> $aUser['zip'],
            'phone' 		=> $aUser['phone'],
            'parent_ref_no' => ((bool)$parent_ref->ref_no) ? $parent_ref->ref_no : $aUser['parent_ref_no'],
            'user_group_id' => $aUser['user_group_id'],
            'isCompany'     => $aUser['isCompany'],
            'ico'           => $aUser['ico'],
            'dic'           => $aUser['dic'],
            'icdph'         => $aUser['icdph'],
            'companyName'   => $aUser['companyName']
        ];
    }

    public function unsetInfoSession()
    {
        $this->section->orderData = null;
    }

    public function addRegister($values)
    {
        $test = $this->getAll()->where('email', $values->email)->fetch();
        if($test) {
            throw new AuthenticationException('Email je už registrovaný');
        }
        $b2bRequest = $values->b2bRequest;
        unset($values->b2bRequest);
        $password = $values['password'];
        $values['password'] = $this->passwords->hash($values->password);
        $values['role'] = UserManager::USER_CUSTOMER;
        $values['affiliate'] = md5($values['email']);
        if($b2bRequest) {
            $values->b2bRequest = UserManager::B2B_REQUEST;
        }
        unset($values->rpassword);
        unset($values->gdpr);
        unset($values->countryCode);
        $this->db->table($this->table)->insert($values);
        $this->user->login($values->email, $password);
    }

    public function addFromReferral($values)
    {
        $test = $this->getAll()->where('email', $values->email)->fetch();
        if($test) {
            throw new AuthenticationException('Email je už registrovaný');
        }
        $values['password'] = $this->passwords->hash($values->password);
        $values['role'] = UserManager::USER_CUSTOMER;
        $values['affiliate'] = md5($values['email']);
        $values['ref_no'] = $this->getNewRefNo();
        $values['user_level_id'] = 1;
        unset($values->rpassword);
        unset($values->countryCode);
        $this->db->table($this->table)->insert($values);
    }

    public function getNewRefNo()
    {
        $refNo = $this->getAll()->max('ref_no');
        return $refNo + 1;
    }

    public function changePassword($userId, $password)
    {
        $hash = $this->passwords->hash($password);
        $this->db->table($this->table)->where('id', $userId)->update(['password' => $hash]);
    }

    public function checkSecret($secret, $orig)
    {
        return $this->passwords->verify($secret, $orig);
    }

    public function changeEmail($userId, $email)
    {
        $this->db->table($this->table)->where('id', $userId)->update(['email' => $email]);
    }

    public function sendLostPassword($email)
    {
        $test = $this->getAll()->where('email', $email)->fetch();
        if(!$test) {
            return;
        }
        $test2 = $this->db->table('lostPassword')->where('user_id', $test->id)->fetch();
        if($test2) {
            return;
        }
        $hash = md5($this->passwords->hash($email.time()));
        $this->db->table('lostPassword')->insert(['user_id' => $test->id, 'hash' => $hash]);
        $this->emailService->sendLostPasswordEmail($email, $hash);
    }

    public function checkLostPassword($hash)
    {
        $userId = null;
        $test = $this->db->table('lostPassword')->where('hash', $hash)->fetch();
        if($test) {
            $userId = $test->user_id;
        }
        return $userId;
    }

    public function removeLostPassword($userId)
    {
        $this->db->table('lostPassword')->where('user_id', $userId)->delete();
    }

    public function getId()
    {
        if($this->user->isLoggedIn()) {
            return $this->user->getId();
        }
        return null;
    }

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getById($userId)
    {
        return $this->getAll()->where('id', $userId);
    }

    public function getByRefNo($refNo)
    {
        return $this->getAll()->where('ref_no', $refNo);
    }

    public function update($userId, $values)
    {
        $this->db->table($this->table)->where('id', $userId)->update($values);
    }

    public function getAllParents($userId, $tree = [])
    {
        $parents = $this->getAll()->where('id', $userId)->fetch();
        if(!$parents) {
            array_shift($tree);
            return array_merge_recursive($tree);
        }
        /*
        $tree[$userId] = [
            'id' => $parents->id,
            'lastName' => $parents->lastName
        ];
        */
        $tree[] = $parents->id;
        return $this->getAllParents($parents->referral_id, $tree);
    }

    public function loginAsUser($userId)
    {
        $row = $this->db->table(UserManager::TABLE_NAME)
            ->where(UserManager::COLUMN_ID, $userId)
            ->fetch();
        $arr = $row->toArray();
        unset($arr[UserManager::COLUMN_PASSWORD_HASH]);
        $identity = new Identity($row[UserManager::COLUMN_ID], $row[UserManager::COLUMN_ROLE], $arr);
        $this->user->login($identity);
//        $this->storage->setIdentity($identity);
//        $this->storage->setAuthenticated(true);
        $section = $this->session->getSection('orderData');
        unset($section['tmp_user_level_id']);
    }

    public function isLoggedUserAdmin()
    {
        if(!$this->user->isLoggedIn()) {
            return false;
        }
        if($this->user->getRoles()[0] == UserManager::USER_ADMIN){
            return true;
        }
        return false;
    }

    public function getReferralEmailFromSession()
    {
        $user = null;
        $section = $this->session->getSection('referral');
        if($section->ref_no) {
            $user = $this->getByRefNo($section->ref_no);
        }
        return $user ? $user->email : null;
    }

    public function getRefNoFromSession()
    {
        $user = null;
        $section = $this->session->getSection('referral');
        if($section->ref_no) {
            $user = $this->getByRefNo($section->ref_no);
        }
        return $section->ref_no ?? null;
    }

    public function getActualUserRefNo($userId)
    {
        $aUser = $this->getById($userId)->fetch();
        return $aUser ? $aUser->ref_no : null;
    }

    public function setNewUserGroupId($userId, $userGroupId)
    {
        $this->db->table($this->table)->where('id', $userId)->update(['user_group_id' => $userGroupId]);
        $this->db->table('user_group_change')->insert([
            'user_id' => $userId,
            'date' => new DateTime(),
            'user_group_id' => $userGroupId
        ]);
        $this->setNewUserLevelId($userId, $userGroupId);
    }

    public function setNewUserLevelId($userId, $userGroupId)
    {
        if($userGroupId >= 5) {
            return;
        }
        $this->db->table($this->table)->where('id', $userId)->update(['user_level_id' => $userGroupId]);
        $this->db->table('user_level_change')->insert([
            'user_id' => $userId,
            'date' => new DateTime(),
            'user_level_id' => $userGroupId
        ]);
    }

    public function setNewUserLevel($userId, $level)
    {
        $this->db->table($this->table)->where('id', $userId)->update(['user_level_id' => $level]);
        $this->db->table('user_level_change')->insert([
            'user_id' => $userId,
            'date' => new DateTime(),
            'user_level_id' => $level
        ]);
        $this->db->table('');
    }

    public function addGroupId()
    {
        $users = $this->getAll();
        foreach ($users as $user) {
            $this->db->table($this->table)->where('id', $user->id)->update(['user_group_id' => $user->user_level->user_group_id]);
        }
    }

    public function getDirectReferees($userId)
    {
        return $this->getAll()->where('referral_id', $userId);
    }
}