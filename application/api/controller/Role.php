<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\User as UserModel;
use app\api\model\Role as RoleModel;
use app\api\model\Module as ModuleModel;

class Role
{
    /****************后台接口 BEGIN*******************/
    /**
     * 获取角色列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据] [0:按角色名搜索] [1:按权限等级搜索]
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
            if (!$isPermission) {
                return $api->msg_405();
            }

            $role = new RoleModel;
            if ($searchValue) {
                if ($searchBasis == "0") {
                    $list = $role->where('name', 'like', '%' . $searchValue . '%')
                        ->select();
                } elseif ($searchBasis == "4") {
                    $list = $role->where('level', $searchValue)
                        ->select();
                } else {
                    return $api->msg_401();
                }
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
     * 管理员详情页获取角色列表
     * @method [GET]
     * @param [string] $token [Token]
     */
    public function getRoleListByAdminDetail()
    {
        $api = new Api;
        $token = input('get.token');

        if (!$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_admin');
            if (!$isPermission) {
                return $api->msg_405();
            }

            // $user = new UserModel;
            // $userData = $user->where('number', $tokenData['data']->number)
            //     ->find();
            // $userLevel = $userData->roleLevel->level;

            $role = new RoleModel;
            $list = $role->field('id, name')
                // ->where('level', 'between', [$userLevel + 1, 125])
                ->where('level', 'between', [0, 125])
                ->select();
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200($list);
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
            if (!$isPermission) {
                return $api->msg_405();
            }

            $role = new RoleModel;
            $data = $role->where('id', $roleId)
                ->find();
            $authorityList = $data->authority;

            $module = new ModuleModel;
            $moduleList = $module->field('id, cn_name')
                ->order('id')
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

        if (!$data || !$token || !$data['id'] || !$data['name'] || $data['level'] == '') {
            return $api->msg_401();
        }
        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_role');
            if (!$isPermission) {
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
            
            $roleName = $role->where('id', $data['id'])
                ->value('name');
            if ($roleName != $data['name']) {
                $haveExisted = $role->where('name', $data['name'])
                    ->find();
                if ($haveExisted) {
                    return $api->return_msg(401, '该角色已存在！');
                }
            }

            $roleData = $role->where('id', $data['id'])
                ->find();
            $authority = $roleData->authority;
            $roleData->allowField(['name', 'level', 'description'])
                ->save($data);

            $authorityIds = array();
            $i = 0;
            foreach ($authority as $authorityItem) {
                $authorityIds[$i] = $authorityItem->id;
                $i++;
            }

            $roleData->authority()->detach($authorityIds);
            $module = $data['module'];
            foreach ($module as $moduleItem) {
                if (isset($moduleItem['ids'])) {
                    $roleData->authority()->attach($moduleItem['ids']);
                }
            }

            return $api->return_msg(200, '修改成功！');
        } catch (\Exception $th) {
            return $api->msg_500();
        }
    }

    /**
     * 获取模块权限列表
     * @method [GET]
     * @param [string] $token [Token]
     */
    public function getModuleList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'insert_role');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $module = new ModuleModel;
            $moduleList = $module->order('id')->select();
            foreach ($moduleList as $moduleItem) {
                $moduleItem->authorityField;
                $moduleItem->ids = array();
            }

            return $api->msg_200($moduleList);
        } catch (\Exception $th) {
            return $api->msg_500();
        }
    }

    /**
     * 添加角色
     * @method [POST]
     * @param [array] $data [角色详情]
     * @param [string] $data['name'] [角色名]
     * @param [string] $data['description'] [角色介绍]
     * @param [string] $data['level'] [权限登记]
     * @param [array] $moduleList [权限列表]
     * @param [string] $token [Token]
     */
    public function addRole()
    {
        $api = new Api;

        $data = input('post.data/a');
        $moduleList = input('post.moduleList/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['name'] || $data['level'] == '') {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_role');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $userData = $user->where('number', $tokenData['data']->number)
                ->find();
            $userRoleLevel = $userData->role->value('level');
            if ($userRoleLevel >= $data['level']) {
                return $api->return_msg(401, '无法添加大于或等于当前用户的权限等级！');
            }

            $role = new RoleModel;
            $haveExisted = $role->where('name', $data['name'])
                ->find();
            if($haveExisted) {
                return $api->return_msg(401, '该角色名已存在！请输入其它名字');
            }

            $result = $role->allowField(true)
                ->save($data);

            $newRole = $role->where('name', $data['name'])
                ->find();
            foreach ($moduleList as $moduleItem) {
                if (isset($moduleItem['ids'])) {
                    $newRole->authority()->attach($moduleItem['ids']);
                }
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if ($result) {
            return $api->return_msg(200, '添加成功！');
        } else {
            return $api->msg_401();
        }
    }

    /**
     * 删除角色
     * @method [POST]
     * @param [string] $rolesId [角色ID]
     * @param [string] $token [Token]
     */
    public function deleteRole()
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
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $userData = $user->where('number', $tokenData['data']->number)
                ->find();
            $userRoleLevel = $userData->role->value('level');

            $role = new RoleModel;
            $changedRoleLevel = $role->where('id', 'in', $rolesId)
                ->column('level');
            
            foreach ($changedRoleLevel as $levelItem) {
                if ($userRoleLevel >= $levelItem) {
                    return $api->return_msg(401, '部分角色权限等级大于或等于当前账户！');
                }
            }

            $result = $role->destroy($rolesId);
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