<?php
namespace app\api\model;

use think\Model;

class Authority extends Model
{
    protected function Module()
    {
        return $this->hasOne('Module', 'id', 'module_id')->field('cn_name as moduleName');
    }

    protected function roles()
    {
        return $this->belongsToMany('Role', 'role_authority', 'role_id', 'capabilities_id');
    }
}
?>