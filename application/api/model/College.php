<?php
namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class College extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    
    protected function department()
    {
        return $this->hasMany('Department', 'college_id', 'id');
    }

    protected function departmentField()
    {
        return $this->department()->field('id, name');
    }

    protected function major()
    {
        return $this->hasMany('Major', 'college_id', 'id');
    }
    
    protected function majorField()
    {
        return $this->major()->field('id, name');
    }

    protected function vClass()
    {
        return $this->hasManyThrough('VClass', 'Major', 'major_id', 'college_id', 'id');
    }

    protected function user()
    {
        return $this->hasMany('User', 'college_id', 'id');
    }

    protected function students()
    {
        return $this->user()
            ->field('id, realname, number, class_id, sex, phone, email, address, description')
            ->where('role_id', 2);
    }
}
?>