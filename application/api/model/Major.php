<?php
namespace app\api\model;

use think\Model;

class Major extends Model
{
    public function college()
    {
        return $this
            ->hasOne("College", 'id', 'college_id')
            ->field('name');
    }
}
?>