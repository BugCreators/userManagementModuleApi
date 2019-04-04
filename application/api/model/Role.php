<?php
namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class Role extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    // 角色表与角色_权限表关联
    protected function roleAuthority()
    {
        return $this->hasMany('role_authority', 'role_id');
    }

    // 角色表与权限表关联
    public function authority()
    {
        return $this->belongsToMany('Authority', 'role_authority', 'authority_id', 'role_id');
    }

    // 角色拥有的权限名
    protected function permission()
    {
        return $this->authority()->field('authority.id, authority.cn_name');
    }

    // 通过权限名搜索权限
    public function authorityByName($name)
    {
        return $this->authority()->where('name', $name)->find();
    }

    protected function user()
    {
        return $this->hasMany('User', 'role_id', 'id');
    }

    protected function adminInfo()
    {
        return $this->user()
            ->field('id, realname, number, role_id, branch_id, sex, phone, email, address, description')
            ->where('branch_id', 'not null');
    }

    protected function branch()
    {
        return $this->hasOne('Branch', 'id', 'branch_id');
    }

    protected function branchName()
    {
        return $this->branch()
            ->field('name as branchName');
    }
}
?>