<?php
namespace app\api\model;

use think\Model;

class VClass extends Model
{
    protected function major()
    {
        return $this
            ->hasOne('Major', 'id', 'major_id')
            ->field('name, college_id');
    }
    
    protected function majorNameByGetAll()
    {
        return $this->major()
            ->field('name as 专业名');
    }

    protected function user()
    {
        return $this->hasMany('User', 'class_id', 'id');
    }

    protected function studentByGetAll()
    {
        return $this->belongsTo('User')
            ->field('name as 班级名');
    }

    protected function students()
    {
        return $this->user()
            ->field('id, realname, number, class_id, sex, phone, email, address, description')
            ->where('role_id', 2);
    }

    protected function getStudentByGetAll()
    {
        return $this->user()
            ->field('number as 学号, realname as 姓名, sex as 性别, class_id, college_id,
            phone as 手机号码, address as 地址, email as 邮箱, description as 个人描述')
            ->where('role_id', 2);
    }

    // protected function 
}
?>