<?php
namespace app\api\model;

use think\Model;

class User extends Model
{
    protected $auto = ['last_ip'];

    protected function setLastIpAttr()
    {
        return request()->ip();
    }

    protected function vclass()
    {
        return $this
            ->hasOne('VClass', 'id', 'v_class_id')
            ->field('name, grade, major_id');
    }

    protected function role()
    {
        return $this
            ->hasOne('Role', 'id', 'role_id')
            ->field('name');
    }
}
?>