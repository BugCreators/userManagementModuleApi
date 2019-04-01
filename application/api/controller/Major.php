<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\Major as MajorModel;
use app\api\model\College as CollegeModel;

class Major
{
    /****************后台接口 BEGIN*******************/
    /**
     * 获取专业列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据] [0:按名字搜索] [1:按学院名搜索]
     * @param [string] $searchValue [搜索值]
     * @param [string] $token [Token]
     */
    public function getMajorList()
    {
        $api = new Api;
        $pageSize = input('post.pageSize');
        $pageIndex = input('post.pageIndex');
        $searchBasis = input('post.searchValue.basis');
        $searchValue = input('post.searchValue.name');
        $token = input('post.token');

        if (!$pageSize || !$pageIndex || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        // try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_major');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $major = new MajorModel;
            if ($searchValue) {
                if ($searchBasis == "0") {
                    $list = $major->where('name', 'like', '%' . $searchValue . '%')
                        ->select();
                    foreach($list as $item) {
                        $item->collegeName = $item->college->name;
                        $item->hidden(['college_id', 'college']);
                    };
                } elseif($searchBasis == "1") {
                    $college = new CollegeModel;
                    $collegeList = $college->field('id, name')
                        ->where('name', 'like', '%' . $searchValue . '%')
                        ->select();
                    $list = array();
                    foreach ($collegeList as $collegeItem) {
                        $majorList = $collegeItem->major;
                        foreach ($majorList as $majorItem) {
                            $majorItem->collegeName = $collegeItem->name;
                            $majorItem->hidden(['college_id', 'college']);
                        }
                        $temp = json_decode(json_encode($majorList), true);
                        $list = array_merge($list, $temp);
                    }
                };
                $count = count($list);
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
            } else {
                $count = $major->count();
                $list = $major
                    ->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->order('id')
                    ->select();
                foreach($list as $item) {
                    $item->collegeName = $item->college->name;
                    $item->hidden(['college_id', 'college']);
                }
            }

            

        // } catch (\Exception $th) {
        //     return $api->msg_500();
        // }
        
        return $api->msg_200([
            'count' => $count,
            'list' => $list
        ]);
    }

    /**
     * 获取专业列表
     * @method [POST]
     * @param [string] $token [Token]
     */
    public function getAllMajorList()
    {
        $api = new Api;
        $token = input('post.token');

        if (!$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_major');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $major = new MajorModel;
            $count = $major->count();
            $list = $major->field('name as 专业名, level as 学历层次,
                college_id, description as 专业概况, train_objective as 培养目标,
                main_course as 主要课程, employment_direction as 就业方向')
                ->select();
            foreach($list as $item) {
                $item->hidden(['college_id']);
                $item->appendRelationAttr('collegeName', ['学院名']);
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200($list);
    }

    /**
     * 获取专业详情
     * @method [GET]
     * @param [string] $marjorId [专业ID]
     * @param [string] $token [Token]
     */
    public function getMajorDetail() 
    {
        $api = new Api;
        $marjorId = input('get.id');
        $token = input('get.token');
        
        if (!$marjorId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_major');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $major = new MajorModel;
            $data = $major->where('id', $marjorId)
                ->find();
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if (!$data) {
            return $api->msg_401();
        } else {
            return $api->msg_200($data);
        }
    }

    /**
     * 编辑专业信息
     * @method [POST]
     * @param [array] $data [专业详情]
     * @param [string] $data['id'] [专业ID]
     * @param [string] $data['name'] [专业名]
     * @param [string] $data['level'] [学历层次]
     * @param [string] $data['college_id'] [学院ID]
     * @param [string] $data['description'] [专业概况]
     * @param [string] $data['train_objective'] [培养目标]
     * @param [string] $data['main_course'] [主要课程]
     * @param [string] $data['employment_direction'] ['就业方向']
     * @param [string] $token [Token]
     */
    public function changeMajor()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['id'] || !$data['name'] || !$data['college_id']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_major');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $major = new MajorModel;
            $result = $major->allowField(['name', 'level', 'college_id', 'description', 'train_objective', 'main_course', 'employment_direction'])
                ->save($data, ['id' => $data['id']]);

            if ($result) {
                $newData = $major->where('id', $data['id'])
                    ->find();
                $newData->appendRelationAttr('collegeName', ['学院名']);
                $newData->hidden(['college_id']);

                return $api->return_msg(200, '修改成功！', $newData);
            } else {
                return $api->return_msg(401, '修改失败，数据未改动！');
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }
    }

    /**
     * 添加专业
     * @method [POST]
     * @param [array] $data [学院详情]
     * @param [string] $data['id'] [专业ID]
     * @param [string] $data['name'] [专业名]
     * @param [string] $data['level'] [学历层次]
     * @param [string] $data['college'] [学院ID]
     * @param [string] $data['description'] [专业概况]
     * @param [string] $data['train_objective'] [培养目标]
     * @param [string] $data['main_course'] [主要课程]
     * @param [string] $data['employment_direction'] ['就业方向']
     * @param [string] $token [Token]
     */
    public function addMajor()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['name'] || !$data['college_id']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_major');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $major = new MajorModel;
            $haveExisted = $major->where('name', $data['name'])
                ->find();
            if($haveExisted) {
                return $api->return_msg(401, '该专业已存在！请输入其它专业');
            }

            $result = $major->allowField(true)
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
     * 批量添加专业
     * @method [POST]
     * @param [array] $majorList [学院列表]
     * @param [string] $token [Token]
     */
    public function importMajorList()
    {
        $api = new Api;

        $majorList = input('post.majorList/a');
        $token = input('post.token');

        if (!$majorList || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_major');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $collegeExist = true;
            $college = new CollegeModel;
            $collegeList = $college->field('id, name')
                ->select();
            foreach($majorList as &$majorItem) {
                foreach($collegeList as $collegeItem) {
                    if ($majorItem['collegeName'] == $collegeItem['name']) {
                        $majorItem['college_id'] = $collegeItem['id'];
                        break;
                    } else {
                        return $api->return_msg(401, $majorItem['collegeName'] . "：该学院不存在！");
                    };
                };
            }

            $major = new MajorModel;
            $result = $major->allowField(true)
                ->saveAll($majorList);
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
     * 删除专业
     * @method [POST]
     * @param [string] $majorId [学院ID]
     * @param [string] $token [Token]
     */
    public function deleteMajor()
    {
        $api = new Api;

        $majorsId = input('post.majorsId/a');
        $token = input('post.token');

        if (!$majorsId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'delete_major');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $major = new MajorModel;

            $result = $major->destroy($majorsId);
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