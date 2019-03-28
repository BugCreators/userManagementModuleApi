<?php
namespace app\api\model;

use think\Model;

class Role extends Model
{
    // 角色表与角色_权限表关联
    public function roleAuthority()
    {
        return $this->hasMany('role_authority', 'role_id');
    }

    // 角色表与权限表关联
    public function authority()
    {
        return $this->belongsToMany('Authority', 'role_authority', 'authority_id', 'role_id');
    }

    // 通过权限名搜索权限
    public function authorityByName($name)
    {
        return $this->authority()->where('name', $name)->find();
    }
}
?>