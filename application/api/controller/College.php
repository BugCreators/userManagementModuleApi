<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\College as CollegeModel;
use app\api\model\User as UserModel;

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

        return $api->msg_200($list);
    }

    /**
     * 获取学院简介
     * @method [GET]
     * @param [int] $collegeId [学院ID]
     */
    public function getCollegeDetail()
    {
        $api = new Api;
        $collegeId = input('get.id');

        if (!$collegeId) {
            return $api->msg_401();
        }
        
        try {
            $college = new CollegeModel;
            $data = $college->where('id', $collegeId)
                ->find();
            $data->major;
        } catch (\Exception $e){
            return $api->msg_500();
            exit;
        }

        if (!$data) {
            return $api->msg_401();
        } else {
            return $api->msg_200($data);
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

        if (!$pageSize || !$pageIndex || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $college = new CollegeModel;
            if ($collegeName) {
                $list = $college->where('name', 'like', $collegeName . '%')
                ->select();
                $count = count($list);
            } else {
                $count = $college->count();
                $list = $college->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->order('id')
                    ->select();
            }    
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200([
            'count' => $count,
            'list' => $list
        ]);
    }

    /**
     * 获取学院详情
     * @method [GET]
     * @param [int] $collegeId [学院ID]
     * @param [string] $token [Token]
     */
    public function getCollegeDetailByAdmin() 
    {
        $api = new Api;
        $collegeId = input('get.id');
        $token = input('get.token');
        
        if (!$collegeId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $college = new CollegeModel;
            $data = $college->where('id', $collegeId)
                ->find();
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if (!$data) {
            return $api->msg_401();
        } else {
            $url = $data->logo;
            if ($url) {
                $data->logo = [
                    'url' => $url,
                ];
            }
            $data->hidden(['logo']);
            return $api->msg_200($data);
        }
    }

    /**
     * 编辑学院信息
     * @method [POST]
     * @param [array] $data [学院详情]
     * @param [string] $data['id'] [学院ID]
     * @param [string] $data['name'] [学院名]
     * @param [string] $data['englishName'] [英文名]
     * @param [string] $data['website'] [学院官网]
     * @param [string] $data['description'] [学院描述]
     * @param [string] $token [Token]
     */
    public function changeCollegeDetail()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['id'] || !$data['name']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $college = new CollegeModel;
            $result = $college->allowField(['name', 'english_name', 'website', 'description'])
                ->save($data, ['id' => $data['id']]);

            if ($result) {
                $newData = $college->where('id', $data['id'])
                    ->find();
                return $api->return_msg(200, '修改成功！', $newData);
            } else {
                return $api->return_msg(401, '修改失败，数据未改动！');
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }
    }

    /**
     * 添加学院
     * @method [POST]
     * @param [array] $data [学院详情]
     * @param [string] $data['name'] [学院名]
     * @param [string] $data['englishName'] [英文名]
     * @param [string] $data['website'] [学院官网]
     * @param [string] $data['description'] [学院描述]
     * @param [string] $token [Token]
     */
    public function addCollege()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['name']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $college = new CollegeModel;
            $haveExisted = $college->where('name', $data['name'])
                ->find();
            if($haveExisted) {
                return $api->return_msg(401, "该学院已存在！请输入其它学院");
            }

            $result = $college->allowField(true)
                ->save($data);
            
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if ($result) {
            return $api->return_msg(200, '添加成功！');
        } else {
            return $api->return_msg(401);
        }
    }

    /**
     * 批量添加学院
     * @method [POST]
     * @param [array] $collegeList [学院列表]
     * @param [string] $token [Token]
     */
    public function importCollegeList()
    {
        $api = new Api;

        $collegeList = input('post.collegeList/a');
        $token = input('post.token');

        if (!$collegeList || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $nameListOfData = array_map(function ($item) {
                return $item['name'];
            }, $collegeList);
            $college = new CollegeModel;
            $nameListOfDataBase = $college->column('name');
            $allNameList = array_merge($nameListOfData, $nameListOfDataBase);
            if (count($allNameList) != count(array_unique($allNameList))) {
                return $api->return_msg(401, "导入失败！部分学院名已存在");
            };

            $result = $college->allowField(true)
                ->saveAll($collegeList);
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if ($result) {
            return $api->return_msg(200, '导入成功！');
        } else {
            return $api->return_msg(401);
        }
    }

    /**
     * 删除学院
     * @method [POST]
     * @param [string] $collegeId [学院ID]
     * @param [string] $token [Token]
     */
    public function deleteCollege()
    {
        $api = new Api;

        $collegeId = input('post.collegesId/a');
        $token = input('post.token');

        if (!$collegeId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'delete_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $college = new CollegeModel;

            $result = $college->destroy($collegeId);
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if ($result) {
            return $api->return_msg(200, '删除成功！' . '删除了' . $result . '条数据');
        } else {
            return $api->return_msg(401, '删除失败！没有数据被删除');
        }
    }
    /******************** END ***********************/
}
?>