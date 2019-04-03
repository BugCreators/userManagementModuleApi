<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\Department as DepartmentModel;
use app\api\model\College as CollegeModel;

class Department
{
    /****************后台接口 BEGIN*******************/
    /**
     * 获取院系列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据] [0:按名字搜索] [1:按学院名搜索]
     * @param [string] $searchValue [搜索值]
     * @param [string] $token [Token]
     */
    public function getDepartmentList()
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

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_department');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $department = new DepartmentModel;
            if ($searchValue) {
                if ($searchBasis == '0') {
                    $list = $department->where('name', 'like', '%' . $searchValue . '%')
                        ->select();
                    foreach($list as $item) {
                        $item->collegeName = $item->college->name;
                        $item->hidden(['college_id', 'college']);
                    };
                } elseif($searchBasis == '1') {
                    $college = new CollegeModel;
                    $collegeList = $college->field('id, name')
                        ->where('name', 'like', '%' . $searchValue . '%')
                        ->select();
                    $list = array();
                    foreach ($collegeList as $collegeItem) {
                        $departmentList = $collegeItem->department;
                        foreach ($departmentList as $departmentItem) {
                            $departmentItem->collegeName = $collegeItem->name;
                            $departmentItem->hidden(['college_id', 'college']);
                        }
                        $temp = json_decode(json_encode($departmentList), true);
                        $list = array_merge($list, $temp);
                    }
                } else {
                    return $api->msg_401();
                };
                $count = count($list);
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
            } else {
                $count = $department->count();
                $list = $department
                    ->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->order('id')
                    ->select();
                foreach($list as $item) {
                    $item->collegeName = $item->college->name;
                    $item->hidden(['college_id', 'college']);
                }
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
     * 获取院系列表
     * @method [POST]
     * @param [string] $token [Token]
     */
    public function getAllDepartmentList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_department');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $department = new DepartmentModel;
            $list = $department->field('name as 院系名, college_id, description as 简介')
                ->select();
            foreach($list as $item) {
                $item->hidden(['college_id']);
                $item->appendRelationAttr('collegeNameByGetAll', ['学院']);
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200($list);
    }

    /**
     * 根据学院ID获取院系列表(专业管理)
     * @method [GET]
     * @param [stirng] $collegeId [学院ID]
     * @param [string] $token [Token]
     */
    public function getDepartmentListByCollegeId()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_department');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $college = new CollegeModel;
            $data = $college->where('id', $collegeId)
                ->find();
            $departmentList = $data->departmentField;
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200($departmentList);
    }

    /**
     * 获取院系详情
     * @method [GET]
     * @param [string] $departmentId [院系ID]
     * @param [string] $token [Token]
     */
    public function getDepartmentDetail() 
    {
        $api = new Api;
        $departmentId = input('get.id');
        $token = input('get.token');
        
        if (!$departmentId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_department');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $department = new DepartmentModel;
            $data = $department->where('id', $departmentId)
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
     * 编辑院系信息
     * @method [POST]
     * @param [array] $data [专业详情]
     * @param [string] $data['id'] [院系ID]
     * @param [string] $data['name'] [院系名]
     * @param [string] $data['college_id'] [学院ID]
     * @param [string] $data['description'] [简介]
     * @param [string] $token [Token]
     */
    public function changeDepartment()
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
            $isPermission = $api->authority($tokenData['data']->number, 'update_department');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $department = new DepartmentModel;
            $haveExisted = $department->where('name', $data['name'])
                ->find();
            if($haveExisted) {
                return $api->return_msg(401, '该院系名已存在！');
            }

            $result = $department->allowField(['name', 'college_id', 'description'])
                ->save($data, ['id' => $data['id']]);

            if ($result) {
                $newData = $department->where('id', $data['id'])
                    ->find();
                $newData->appendRelationAttr('collegeNameByGetAll', ['学院']);
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
     * 添加院系
     * @method [POST]
     * @param [array] $data [院系详情]
     * @param [string] $data['name'] [院系名]
     * @param [string] $data['college_id'] [学院ID]
     * @param [string] $data['description'] [简介]
     * @param [string] $token [Token]
     */
    public function addDepartment()
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
            $isPermission = $api->authority($tokenData['data']->number, 'insert_department');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $department = new DepartmentModel;
            $haveExisted = $department->where('name', $data['name'])
                ->find();
            if($haveExisted) {
                return $api->return_msg(401, '该院系已存在！请输入其它院系');
            }

            $result = $department->allowField(true)
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
     * 批量添加院系
     * @method [POST]
     * @param [array] $departmentList [院系列表]
     * @param [string] $token [Token]
     */
    public function importDepartmentList()
    {
        $api = new Api;

        $departmentList = input('post.departmentList/a');
        $token = input('post.token');

        if (!$departmentList || !$token) {
            return $api->msg_401();
        }

        foreach ($departmentList as $departmentItem) {
            if (!$departmentItem['name'] || !$departmentItem['collegeName']) {
                return $api->msg_401();
            }
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_department');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $nameListOfData = array_map(function($item) {
                return $item['name'];
            }, $departmentList);
            $department = new DepartmentModel;
            $nameListOfDataBase = $department->column('name');
            $allNameList = array_merge($nameListOfData, $nameListOfDataBase);
            if (count($allNameList) != count(array_unique($allNameList))) {
                return $api->return_msg(401, '导入失败！部分院系名已存在');
            };

            $college = new CollegeModel;
            $collegeList = $college->field('id, name')
                ->select();
            $collegeIsExist = true;
            foreach($departmentList as &$departmentItem) {
                foreach($collegeList as $collegeItem) {
                    if ($departmentItem['collegeName'] == $collegeItem['name']) {
                        $departmentItem['college_id'] = $collegeItem['id'];
                        $collegeIsExist = false;
                        break;
                    }
                };
                if ($collegeIsExist) {
                    return $api->return_msg(401, $departmentItem['collegeName'] . '：该学院不存在！');
                } else {
                    $collegeIsExist = true;
                }
            }

            $result = $department->allowField(true)
                ->saveAll($departmentList);
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
     * 删除院系
     * @method [POST]
     * @param [string] $departmentsId [院系ID]
     * @param [string] $token [Token]
     */
    public function deleteDepartments()
    {
        $api = new Api;

        $departmentsId = input('post.departmentsId/a');
        $token = input('post.token');

        if (!$departmentsId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'delete_department');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $department = new DepartmentModel;

            $result = $department->destroy($departmentsId);
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