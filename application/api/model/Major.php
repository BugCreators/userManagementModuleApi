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
        return $this
            ->hasOne("College", 'id', 'college_id')
            ->field('id, name');
    }

    protected function collegeName()
    {
        return $this
            ->hasOne("College", 'id', 'college_id')
            ->field('name as 学院名');
    }
}
?>