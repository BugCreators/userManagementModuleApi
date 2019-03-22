<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\College as CollegeModel;

class College
{
    /**
     * 首页获取学院列表
     * @method [GET] [POST]
     */
    public function getCollegeList()
    {
        $api = new Api;
        try {
            $college = new CollegeModel;
            $list = $college->order('id')
                ->field('id, logo, name')
                ->select();
        } catch (\Exception $e){
            return $api->return_msg(500, '系统错误，请联系管理员！');
            exit;
        };
        return $api->return_msg(200, '', $list);
    }

    /**
     * 获取学院简介
     * @method [GET]
     * @param [int] $id [学院id]
     */
    public function getCollegeDetail()
    {
        $api = new Api;
        $id = input('get.id');
        if (!$id) {
            return $api->return_msg(400, '学院id为空！');
        }
        
        try {
            $college = new CollegeModel;
            $data = $college->where('id', $id)
                ->find();
            $data->major;
        } catch (\Exception $e){
            return $api->return_msg(500, '系统错误，请联系管理员！');
            exit;
        }
        if (!$data) {
            return $api->return_msg(401, '找不到该学院！');
        } else {
            return $api->return_msg(200, '', $data);
        }
    }
}
?>