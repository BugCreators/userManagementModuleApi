<?php
namespace app\api\model;

use think\Model;

class Capabilities extends Model
{
    public function roles()
    {
        return $this->belongsToMany('Role', 'role_capabilities', 'role_id', 'capabilities_id');
    }
}
?>