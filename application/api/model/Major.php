<?php
namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class Major extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    protected function college()
    {
        return $this->hasOne('College', 'id', 'college_id')
            ->field('id, name');
    }

    protected function department()
    {
        return $this->hasOne('Department', 'id', 'department_id');
    }

    protected function departmentName()
    {
        return $this->department()
            ->field('name as departmentName');
    }

    protected function departmentNameByGetAll()
    {
        return $this->department()
            ->field('name as 教学系');
    }

    protected function belongsToCollege() // 与college()效果一样
    {
        return $this->belongsTo('College');
    }

    protected function collegeNameByGetAll()
    {
        return $this->college()
            ->field('name as 学院');
    }

    protected function vclass() 
    {
        return $this->hasMany('VClass', 'major_id', 'id');
    }

    protected function classField()
    {
        return $this->vclass()
            ->field('id, grade, name');
    }
}
?>