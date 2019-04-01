<?php
namespace app\api\model;

use think\Model;

class VClass extends Model
{
    public function major()
    {
        return $this
            ->hasOne('Major', 'id', 'major_id')
            ->field('name, college_id');
    }

    public function user()
    {
        return $this->hasMany('User', 'class_id', 'id');
    }

    public function majorNameByGetAll()
    {
        return $this->major()
            ->field('name as 专业名');
    }
}
?>