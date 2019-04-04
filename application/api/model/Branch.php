<?php
namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class Branch extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    protected function admin()
    {
        return $this->hasMany('User', 'branch_id', 'id');
    }

    protected function adminInfo()
    {
        return $this->admin()
            ->field('id, realname, number, role_id, branch_id, sex, phone, email, address, description');
    }
}
?>