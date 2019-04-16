<?php
namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class User extends Model
{
    protected $auto = ['last_ip'];
    
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    protected function setLastIpAttr()
    {
        return request()->ip();
    }

    // public function getSexAttr($value)
    // {
    //     $sex = [0 => '女', 1 => '男'];
    //     return $sex[$value];
    // }

    protected function vclass()
    {
        return $this->hasOne('VClass', 'id', 'class_id')
            ->field('name, grade, major_id');
    }

    protected function classNameByGetAll()
    {
        return $this->hasOne('VClass', 'id', 'class_id')
            ->field('concat(grade, name) as 班级名');
    }

    protected function college()
    {
        return $this->hasOne('College', 'id', 'college_id');
    }

    protected function collegeName()
    {
        return $this->college()
            ->field('name as collegeName');
    }

    protected function collegeNameByGetAll()
    {
        return $this->college()
            ->field('name as 学院名');
    }

    protected function role()
    {
        return $this->hasOne('Role', 'id', 'role_id')
            ->field('id, name');
    }

    protected function roleName() 
    {
        return $this->role()
            ->field('name as roleName');
    }

    protected function roleLevel()
    {
        return $this->hasOne('Role', 'id', 'role_id')
            ->field('level');
    }

    protected function branch()
    {
        return $this->hasOne('Branch', 'id', 'branch_id');
    }

    protected function branchId()
    {
        return $this->branch()
            ->field('id as branch_id');
    }

    protected function branchName()
    {
        return $this->branch()
            ->field('name as branchName');
    }
}
?>