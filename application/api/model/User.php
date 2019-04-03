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

    protected function roleLevel()
    {
        return $this->hasOne('Role', 'id', 'role_id');
    }

}
?>