<?php
namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class Department extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    protected function college()
    {
        return $this->hasOne('College', 'id', 'college_id')
            ->field('id, name');
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
}
?>