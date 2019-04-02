<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\Role as RoleModel;

class Role
{
    /****************后台接口 BEGIN*******************/
    /**
     * 获取角色列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据]
     * @param [string] $searchValue [搜索值]
     * @param [string] $token [Token]
     */
    public function getRoleList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_role');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $role = new RoleModel;
            if ($searchValue) {
                $list = $role->where('name', 'like', '%' . $searchValue . '%')
                    ->select();
                $count = count($list);
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
            } else {
                $count = $role->count();
                $list = $role
                    ->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->order('id')
                    ->select();
                foreach($list as $item) {
                    $item->permission;
                    $item->hidden(['permission.pivot']);
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
            if ($isPermission == 0) {
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
            if ($isPermission == 0) {
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
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $department = new DepartmentModel;
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
            if ($isPermission == 0) {
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
     * 删除院系
     * @method [POST]
     * @param [string] $rolesId [院系ID]
     * @param [string] $token [Token]
     */
    public function deleteDepartments()
    {
        $api = new Api;

        $rolesId = input('post.rolesId/a');
        $token = input('post.token');

        if (!$rolesId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'delete_role');
            if ($isPermission == 0) {
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