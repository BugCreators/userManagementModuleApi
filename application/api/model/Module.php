<?php
namespace app\api\model;

use think\Model;

class Module extends Model
{
    protected function authority()
    {
        return $this->hasMany('Authority', 'module_id', 'id');
    }
}
?>