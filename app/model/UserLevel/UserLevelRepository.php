<?php


namespace App\Model\UserLevel;


use App\Model\BaseRepository;
use App\Model\Services\AppSettingsService;
use App\Model\Services\UploadService;
use Nette\Database\Context;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class UserLevelRepository extends BaseRepository
{

    private $db, $table = 'user_level', $appSettingsService, $productRepository, $tableGroup = 'user_group', $subLevels = [];

    public function __construct(Context $db, AppSettingsService $appSettingsService)
    {
        $this->db = $db;
        $this->appSettingsService = $appSettingsService;
    }

    public function getAll()
    {
        return $this->db->table($this->table);
    }

    public function getForSelect()
    {
        return $this->getAll()->fetchPairs('id', 'name');
    }

    public function getById($id)
    {
        return $this->getAll()->where('id', $id);
    }

    public function update($values, $id = null)
    {
        if($id) {
            $this->getAll()->where('id', $id)->update($values);
        } else {
            $this->getAll()->insert($values);
        }
    }

    public function remove($id)
    {
        try {
            $this->getById($id)->where('id', $id)->delete();
            return true;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function getAllGroups()
    {
        return $this->db->table($this->tableGroup);
    }

    public function getGroupById($id)
    {
        return $this->getAllGroups()->where('id', $id);
    }

    public function getGroupIdByLevel($userLevelId)
    {
        $userLevel = $this->getById($userLevelId)->fetch();
        return $userLevel ? $userLevel->user_group_id : null;
    }

    public function getGroupsToSelect()
    {
        return $this->getAllGroups()->fetchPairs('id', 'name');
    }

    public function setValue($id, $key, $value)
    {
        $this->db->table('user_level')->where('id', $id)->update([$key => $value]);
    }

    public function getSubLevels($parentId, $tree = [])
    {
        $subs = $this->getAll()->where('parent_id', $parentId)->fetch();
        if(!$subs) {
            return array_merge_recursive($tree);
        }
        $tree[$parentId] = [
            'id' => $subs->id,
            'name' => $subs->name
        ];
        return $this->getSubLevels($subs->id, $tree);
    }

    public function saveUserLevelCommission($values)
    {
        $userLevels = $this->getAll()->order('ord');
        foreach ($userLevels as $userLevel) {
            $subs = $this->getSubLevels($userLevel->id);
            $tree[$userLevel->id] = $subs;
        }
        foreach ($userLevels as $userLevel) {
            $commission = [];
            foreach ($tree as $key => $item) {
                if(isset($values[$userLevel->id.'s'.$key])) {
                    $commission[$key] = str_replace(',', '.', $values[$userLevel->id.'s'.$key]);
                }
            }
            $this->db->table('user_level')->where('id', $userLevel->id)->update(['commission_level' => json_encode($commission)]);
        }
    }

    public function getNewLevelByTurnover($turnover)
    {
        $level = $this->getAll()
            ->where('min_turnover >= ?', $turnover)
            ->where('min_turnover <= ?', $turnover)
            ->fetch();
        return $level->user_group_id ?? null;
    }

    public function getLevelByTurnover($turnover)
    {
        $level = $this->getAll()
            ->where('min_turnover <= ?', $turnover)
            ->order('min_turnover DESC')
            ->fetch();
        return $level->id ?? null;
    }

    public function getBonuses()
    {
        $levels = $this->getAll();
        $out = [];
        foreach ($levels as $level) {
            $out[$level->id] = [
                1 => $level->bonus,
                2 => $level->bonus_cz
            ];
        }
        return $out;
    }
}