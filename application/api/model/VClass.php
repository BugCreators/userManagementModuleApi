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
}
?>