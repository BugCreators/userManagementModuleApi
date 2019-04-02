<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\User as UserModel;
use app\api\model\Role as RoleModel;
use app\api\model\Authority as AuthorityModel;
use app\api\model\Module as ModuleModel;

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
     * 获取角色详情
     * @method [GET]
     * @param [string] $roleId [角色ID]
     * @param [string] $token [Token]
     */
    public function getRoleDetail() 
    {
        $api = new Api;
        $roleId = input('get.id');
        $token = input('get.token');
        
        if (!$roleId || !$token) {
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
            $data = $role->where('id', $roleId)
                ->find();
            $authorityList = $data->authority;

            $module = new ModuleModel;
            $moduleList = $module->field('id, cn_name')
                ->select();
            $ids = array();
            $i = 0;
            foreach ($moduleList as $moduleItem) {
                $authorityOfModule = $moduleItem->authorityField;
                foreach ($authorityOfModule as $authorityOfModuleItem) {
                    foreach ($authorityList as $authorityItem) {
                        if ($authorityOfModuleItem->id == $authorityItem->id) {
                            if ($authorityItem->pivot->permission == 1) {
                                $ids[$i] = $authorityItem->id;
                                $i++;
                            }
                        }
                    }
                }
                $moduleItem->ids = $ids;
                $ids = array();
                $i = 0;
            }
            $data->module = $moduleList;
            $data->hidden(['authority']);
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
     * 编辑角色信息
     * @method [POST]
     * @param [array] $data [专业详情]
     * @param [string] $data['id'] [角色ID]
     * @param [string] $data['name'] [角色名]
     * @param [string] $data['description'] [角色介绍]
     * @param [string] $data['level'] [权限等级]
     * @param [string] $token [Token]
     */
    public function changeRole()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['id'] || !$data['name'] || $data['level'] == "") {
            return $api->msg_401();
        }
        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        // try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_role');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $userData = $user->where('number', $tokenData['data']->number)
                ->find();
            $userRoleLevel = $userData->role->value('level');
            if ($userRoleLevel >= $data['level']) {
                return $api->return_msg(401, '无法修改大于或等于当前用户的权限等级！');
            }

            $role = new RoleModel;
            $changedRoleLevel = $role->where('id', $data['id'])->value('level');
            if ($userRoleLevel >= $changedRoleLevel) {
                return $api->return_msg(401, '无法修改大于或等于当前用户权限等级的角色！');
            }

            $roleData = $role->where('id', $data['id'])
                ->find();
            $roleData->allowField(['name', 'level', 'description'])
                ->save($data);
            
            $authority = new AuthorityModel;
            $authorityIds = $authority->column('id');

            return $authorityIds;
            $roleData->authority()->attach($authorityIds, ['permission' => 0]);
            $module = $data['module'];
            foreach ($module as $moduleItem) {
                if (isset($moduleItem['ids'])) {
                    $roleData->authority()->attach($moduleItem['ids'], ['permission' => 1]);
                }
            }

            return $api->return_msg(200, '修改成功！');
        // } catch (\Exception $th) {
        //     return $api->msg_500();
        // }
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