<?php
namespace app\api\model;

use think\Model;

class Role extends Model
{
    // 角色表与角色_权限表关联
    public function roleCapabilities()
    {
        return $this->hasMany('role_capabilities', 'role_id');
    }

    // 角色表与权限表关联
    public function capabilities()
    {
        return $this->belongsToMany('Capabilities', 'role_capabilities', 'capabilities_id', 'role_id');
    }

    public function intoBackstage($roleId)
    {
        return $this->roleCapabilities()
            ->where('role_id', $roleId)
            ->find()
            ->permission;
    }

    public function selectCapabilities($roleId)
    {
        return $this->capabilities()
            ->where('name', 'LIKE', 'select_')
            ->select();
    }
}
?>