<?php
namespace app\api\model;

use think\Model;

class College extends Model
{
    protected function Major()
    {
        return $this->hasMany('Major', 'college_id', 'id');
    }
}
?>