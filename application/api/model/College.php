<?php
namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class College extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    
    protected function Major()
    {
        return $this->hasMany('Major', 'college_id', 'id');
    }
}
?>