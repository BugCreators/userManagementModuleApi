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
            return $api->msg_500();
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
            return $api->msg_500();
            exit;
        }
        if (!$data) {
            return $api->return_msg(401, '找不到该学院！');
        } else {
            return $api->return_msg(200, '', $data);
        }
    }

    /****************后台接口 BEGIN*******************/
    /**
     * 获取学院列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $collegeName [学院名]
     * @param [string] $token [Token]
     */
    public function getCollegeListByAdmin()
    {
        $api = new Api;
        $pageSize = input('post.pageSize');
        $pageIndex = input('post.pageIndex');
        $collegeName = input('post.searchValue.name');
        $token = input('post.token');

        if (!$pageSize || !$pageIndex) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $college = new CollegeModel;
            if ($collegeName) {
                $list = $college->where('name', $collegeName)
                ->select();
                $count = count($list);
            } else {
                $count = $college->count();
                $list = $college->where('id', '>', $pageSize * ($pageIndex - 1))
                    ->limit($pageSize)
                    ->order('id')
                    ->select();
            }    
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->return_msg(200, '', [
            'count' => $count,
            'list' => $list
        ]);
    }


    /******************** END ***********************/
}
?>