<?php
namespace app\api\model;

use think\Model;

class Authority extends Model
{
    public function roles()
    {
        return $this->belongsToMany('Role', 'role_authority', 'role_id', 'capabilities_id');
    }
}
?>