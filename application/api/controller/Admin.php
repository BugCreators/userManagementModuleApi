<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\User as UserModel;
use app\api\model\Role as RoleModel;
use app\api\model\Branch as BranchModel;

class Admin
{
    /****************后台接口 BEGIN*******************/
    /**
     * 获取管理员列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据] [0:按姓名搜索] [6:按部门搜索] [7:按角色搜索]
     * @param [string] $searchValue [搜索值]
     * @param [string] $token [Token]
     */
    public function getAdminList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_admin');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $list = array();
            if ($searchValue) {
                if ($searchBasis == '0') {
                    $list = $user->field('id, realname, number, role_id, branch_id, sex, phone, email, address, description')
                        ->where('realname', 'like', '%' . $searchValue . '%')
                        ->where('branch_id', 'not null')
                        ->select();
                    foreach($list as $item) {
                        $item->appendRelationAttr('roleName', ['roleName']);
                        $item->appendRelationAttr('branchName', ['branchName']);
                        $item->hidden(['branch_id', 'role_id']);
                    }
                } elseif ($searchBasis == '6') {
                    $branch = new BranchModel;
                    $branchList = $branch->where('name', 'like', '%' . $searchValue . '%')
                        ->select();
                    foreach ($branchList as $branchItem) {
                        $list = array_merge($list, $branchItem->adminInfo);
                    }
                    foreach($list as $item) {
                        $item->branchName = $item->branch->name;
                        $item->appendRelationAttr('roleName', ['roleName']);
                        $item->hidden(['branch_id', 'role_id', 'branch']);
                    }
                } elseif ($searchBasis == '7') {
                    $role = new RoleModel;
                    $roleList = $role->where('name', 'like', '%' . $searchValue . '%')
                        ->select();
                
                    foreach ($roleList as $roleItem) {
                        $list = array_merge($list, $roleItem->adminInfo);
                    }
                    foreach($list as $item) {
                        $item->roleName = $item->role->name;
                        $item->appendRelationAttr('branchName', ['branchName']);
                        $item->hidden(['branch_id', 'role_id']);
                    }
                } else {
                    return $api->msg_401();
                }
                $count = count($list);
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
            } else {
                $count = $user->where('branch_id', 'not null')
                    ->count();
                $list = $user->field('id, realname, number, role_id, branch_id, sex, phone, email, address, description')
                    ->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->where('branch_id', 'not null')
                    ->order('id')
                    ->select();
                foreach($list as $item) {
                    $item->appendRelationAttr('roleName', ['roleName']);
                    $item->appendRelationAttr('branchName', ['branchName']);
                    $item->hidden(['branch_id', 'role_id']);
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
     * 获取管理员详情
     * @method [GET]
     * @param [string] $adminId [管理员ID]
     * @param [string] $token [Token]
     */
    public function getAdminDetail() 
    {
        $api = new Api;
        $adminId = input('get.id');
        $token = input('get.token');
        
        if (!$adminId || !$token) {
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

            $user = new UserModel;
            $data = $user->where('id', $adminId)
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
     * 重置管理员密码
     * @method [GET]
     * @param [int] $adminId [管理员ID]
     * @param [token] $token [Token]
     */
    public function resetPassword()
    {
        $api = new Api;
        $adminId = input('get.id');
        $token = input('get.token');

        if (!$adminId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        }

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_admin');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $changeByOwn = false;
            $userData = $user->where('number', $tokenData['data']->number)
                ->find();
            $userLevel = $userData->roleLevel->level;

            $changedUser = $user
                ->where('id', $adminId)
                ->find();
            $changeUserLevel = $changedUser->roleLevel->level;

            if ($userLevel > $changeUserLevel) {
                return $api->msg_405_not_enough();
            }
            
            $result = $changedUser->save(['password' => md5($changedUser['number'])], ['id' => $adminId]);

            if ($userData['id'] == $changedUser['id']) {
                $changeByOwn = true;
            }

        } catch (\Exception $e) {
            return $api->msg_500();
        }

        if ($result) {
            return $api->return_msg(200, '重置成功！', [
                'changeByOwn' => $changeByOwn
            ]);
        } else {
            return $api->return_msg(401, '重置失败，数据未改动！');
        }
    }

    /**
     * 编辑管理员信息
     * @method [POST]
     * @param [array] $data [管理员详情]
     * @param [string] $data['id'] [管理员ID]
     * @param [string] $data['realname'] [姓名]
     * @param [string] $data['number'] [职工]
     * @param [string] $data['sex'] [性别]
     * @param [string] $data['branch_id'] [部门ID]
     * @param [string] $data['role_id'] [角色ID]
     * @param [string] $data['phone'] [手机]
     * @param [string] $data['address'] [地址]
     * @param [string] $data['email'] ['邮箱']
     * @param [string] $data['description'] [个人描述]
     * @param [string] $token [Token]
     */
    public function changeAdmin()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['id'] || !$data['realname'] || strlen($data['number']) != 11 || !$data['branch_id'] || !$data['role_id']) {
            return $api->msg_401();
        }
        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_admin');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $userData = $user->where('number', $tokenData['data']->number)
                ->find();
            $userLevel = $userData->roleLevel->level;

            $changedUser = $user->where('id', $data['id'])
                ->find();
            $changedUserLevel = $changedUser->roleLevel->level;

            if ($userLevel >= $changedUserLevel) {
                return $api->msg_405_not_enough();
            }

            if ($data['number'] != $changedUser['number']) {
                return $api->return_msg(401, '该职工号已存在！');
            }

            $result = $changedUser->allowField(['realname', 'number', 'sex', 'branch_id',
            'role_id', 'description', 'phone', 'address', 'email'])
                ->save($data);

            return $api->return_msg(200, '修改成功！');
        } catch (\Exception $th) {
            return $api->msg_500();
        }
    }

    /**
     * 添加角色
     * @method [POST]
     * @param [array] $data [管理员详情]
     * @param [string] $data['realname'] [姓名]
     * @param [string] $data['number'] [职工号]
     * @param [string] $data['sex'] [性别]
     * @param [string] $data['branch_id'] [部门ID]
     * @param [string] $data['role_id'] [角色ID]
     * @param [string] $data['phone'] [手机]
     * @param [string] $data['address'] [地址]
     * @param [string] $data['email'] ['邮箱']
     * @param [string] $data['description'] [个人描述]
     * @param [string] $token [Token]
     */
    public function addAdmin()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['realname'] || strlen($data['number']) != 11 || !$data['branch_id'] || !$data['role_id']) {
            return $api->msg_401();
        }
        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_admin');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $userData = $user->where('number', $tokenData['data']->number)
                ->find();
            $userLevel = $userData->roleLevel->level;

            $role = new RoleModel;
            $roleLevel = $role->where('id', $data['role_id'])
                ->value('level');

            if ($userLevel >= $roleLevel) {
                return $api->msg_405_not_enough();
            }

            $haveExisted = $user->where('number', $data['number'])
                ->where('branch_id', 'not null')
                ->find();
            if ($haveExisted) {
                return $api->return_msg(401, '该职工号已存在！');
            }

            $data['password'] = md5($data['number']);

            $result = $user->allowField(['realname', 'password', 'number', 'sex', 'branch_id',
            'role_id', 'description', 'phone', 'address', 'email'])
                ->save($data);
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
     * 删除管理员
     * @method [POST]
     * @param [string] $adminsId [角色ID]
     * @param [string] $token [Token]
     */
    public function deleteAdmin()
    {
        $api = new Api;

        $adminsId = input('post.adminsId/a');
        $token = input('post.token');

        if (!$adminsId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'delete_admin');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $userData = $user->where('number', $tokenData['data']->number)
                ->find();
            $userLevel = $userData->role->value('level');

            $changedUsers = $user->where('id', 'in', $adminsId)
                ->select();
            
            foreach ($changedUsers as $changedUser) {
                $changedUserLevel = $changedUser->roleLevel->level;
                if ($userLevel >= $changedUserLevel) {
                    return $api->return_msg(405, '部分角色权限等级大于或等于当前账户！');
                }
            }

            $result = $user->destroy($adminsId);
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