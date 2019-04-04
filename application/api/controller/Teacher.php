<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\User as UserModel;
use app\api\model\VClass as ClassModel;
use app\api\model\Major as MajorModel;
use app\api\model\College as CollegeModel;

class Teacher
{
    /**
     * 获取教师列表
     * @method [GET]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据] [0: 按教师名搜索] [1:按学院名搜索]
     * @param [string] $searchValue [搜索值]
     * @param [token] $token [Token]
     */
    public function getTeacherList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_teacher');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $list = array();

            if ($searchValue) {
                switch ($searchBasis) {
                    case '0':
                        $user = new UserModel;
                        $list = $user->field('id, realname, number, college_id, sex, phone, email, address, description')
                            ->where('realname', 'like', '%' . $searchValue . '%')
                            ->where('role_id', '<>', 2)
                            ->where('college_id', 'not null')
                            ->select();
                        foreach ($list as $item) {
                            $item->appendRelationAttr('collegeName', ['collegeName']);
                            $item->hidden(['vclass', 'class_id']);
                        }
                        break;
                    case '1':
                        $college = new CollegeModel;
                        $collegeList = $college->where('name', 'like', '%' . $searchValue . '%')
                            ->select();
                        if ($collegeList) {
                            foreach ($collegeList as $collegeItem) {
                                $list = array_merge($list, $collegeItem->teachers);
                            };
                        }
                        foreach ($list as $item) {
                            $item->collegeName = $item->college->name;
                            $item->hidden(['vclass', 'class_id', 'college']);
                        }
                        break;
                    default:
                        return $api->msg_401();
                        break;
                };
                $count = count($list);
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
            } else {
                $count = $user->where('role_id', '<>', 2)
                    ->where('college_id', 'not null')
                    ->count();
                $list = $user->field('id, realname, number, college_id, sex, phone, email, address, description')
                    ->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->where('role_id', '<>', 2)
                    ->where('college_id', 'not null')
                    ->order('id')
                    ->select();
                foreach ($list as $item) {
                    $item->appendRelationAttr('collegeName', ['collegeName']);
                    $item->hidden(['vclass', 'class_id']);
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
     * 获取教师列表
     * @method [POST]
     * @param [string] $token [Token]
     */
    public function getAllTeacherList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_teacher');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $list = $user->field('number as 职工号, realname as 姓名, sex as 性别, college_id,
                phone as 手机号码, address as 地址, email as 邮箱, description as 个人描述')
                ->where('role_id', '<>', 2)
                ->where('college_id', 'not null')
                ->select();
            foreach ($list as $item) {
                $item->appendRelationAttr('collegeNameByGetAll', ['学院名']);
                $item->hidden(['class_id', 'college_id']);
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200($list);
    }

    /**
     * 获取教师详情
     * @method [GET]
     * @param [string] $teacherId [教师ID]
     * @param [string] $token [Token]
     */
    public function getTeacherDetail()
    {
        $api = new Api;
        $teacherId = input('get.id');
        $token = input('get.token');
        
        if (!$teacherId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_teacher');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $data = $user->field('id, realname, number, college_id, sex, phone, email, address, description')
                ->where('id', $teacherId)
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
     * 重置教师密码
     * @method [GET]
     * @param [int] $teacherId [教师ID]
     * @param [token] $token [Token]
     */
    public function resetPassword()
    {
        $api = new Api;
        $teacherId = input('get.id');
        $token = input('get.token');

        if (!$teacherId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        }

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_teacher');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $changeByOwn = false;
            $userData = $user->where('number', $tokenData['data']->number)
                ->find();
            $userLevel = $userData->roleLevel->level;

            $changedUser = $user
                ->where('id', $teacherId)
                ->find();
            $changeUserLevel = $changedUser->roleLevel->level;

            if ($userLevel > $changeUserLevel) {
                return $api->return_msg(401, '无法重置，权限不足！');
            }

            $result = $changedUser->save(['password' => md5($changedUser->number)]);

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
     * 编辑教师信息
     * @method [POST]
     * @param [array] $data [教师详情]
     * @param [string] $data['id'] [教师ID]
     * @param [string] $data['realname'] [教师名]
     * @param [string] $data['number'] [职工号]
     * @param [string] $data['sex'] [性别]
     * @param [string] $data['college_id'] [学院ID]
     * @param [string] $data['phone'] [手机]
     * @param [string] $data['address'] [地址]
     * @param [string] $data['email'] ['邮箱']
     * @param [string] $data['description'] [个人描述]
     * @param [string] $token [Token]
     */
    public function changeTeacher()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['id'] || !$data['realname'] || strlen($data['number']) != 11 || !$data['college_id']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_teacher');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;

            $userData = $user->where('number', $tokenData['data']->number)
                ->where('role_id', '<>', 2)
                ->find();
            $userLevel = $userData->roleLevel->level;

            $changedUser = $user->where('number', $data['number'])
                ->where('role_id', '<>', 2)
                ->find();
            $changedUserLevel = $changedUser->roleLevel->level;

            if ($userLevel >= $changedUserLevel) {
                return $api->msg_405_not_enough();
            }

            if ($data['id'] != $changedUser['id']) {
                return $api->return_msg(401, '职工号已存在！');
            };

            $result = $user->allowField(['realname', 'number', 'sex', 'college_id',
                'description', 'phone', 'address', 'email'])
                ->save($data, ['id' => $data['id']]);
            
        } catch (\Exception $th) {
            return $api->msg_500();
        }
        
        if ($result) {
            return $api->return_msg(200, '修改成功！');
        } else {
            return $api->return_msg(401, '修改失败，数据未改动！');
        }
    }

    /**
     * 添加教师
     * @method [POST]
     * @param [array] $data [教师详情]
     * @param [string] $data['realname'] [教师名]
     * @param [string] $data['number'] [职工号]
     * @param [string] $data['sex'] [性别]
     * @param [string] $data['college_id'] [学院ID]
     * @param [string] $data['phone'] [手机]
     * @param [string] $data['address'] [地址]
     * @param [string] $data['email'] ['邮箱']
     * @param [string] $data['description'] [个人描述]
     * @param [string] $token [Token]
     */
    public function addTeacher()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['realname'] || strlen($data['number']) != 11 || !$data['college_id']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_teacher');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $haveExisted = $user->where('number', $data['number'])
                ->where('role_id', '<>', 2)
                ->find();
            if($haveExisted) {
                return $api->return_msg(401, '职工号已存在！');
            }

            $data['role_id'] = 3;
            $data['password'] = md5($data['number']);

            $result = $user->allowField(true)
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
     * 批量添加教师
     * @method [POST]
     * @param [array] $teacherList [列表]
     * @param [string] $token [Token]
     */
    public function importTeacherList()
    {
        $api = new Api;

        $data = input('post.data/a');
        $teacherList = input('post.teacherList/a');
        $token = input('post.token');

        if (!$teacherList || !$token) {
            return $api->msg_401();
        }
        
        foreach ($teacherList as $teacherItem) {
            if (!$teacherItem['realname'] || strlen($teacherItem['number']) != 11 || !$teacherItem['collegeName']) {
                return $api->msg_401();
            };
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_student');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $numberOfData = array_map(function($item) {
                return $item['number'];
            }, $teacherList);
            $user = new UserModel;
            $numberOfDatabase = $user->where('role_id', '<>', 2)->column('number');
            $allNumberList = array_merge($numberOfData, $numberOfDatabase);
            if (count($allNumberList) != count(array_unique($allNumberList))) {
                return $api->return_msg(401, '导入失败！部分职工号已存在');
            };

            $college = new CollegeModel;
            $collegeList = $college->field('id, name')
                ->select();
            $collegeIsExist = true;
            
            foreach($teacherList as &$teacherItem) {
                $teacherItem['role_id'] = 3;
                $teacherItem['password'] = md5($teacherItem['number']);
                foreach($collegeList as $collegeItem) {
                    if ($teacherItem['collegeName'] == $collegeItem['name']) {
                        $teacherItem['college_id'] = $collegeItem['id'];
                        $collegeIsExist = false;
                        break;
                    }
                };
                if ($collegeIsExist) {
                    return $api->return_msg(401, $teacherItem['collegeName'] . '：该学院不存在！');
                } else {
                    $collegeIsExist = true;
                }
            }

            $result = $user->allowField(true)
                ->saveAll($teacherList);

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
     * 删除教师
     * @method [POST]
     * @param [string] $teachersId [教师ID]
     * @param [string] $token [Token]
     */
    public function deleteTeacher()
    {
        $api = new Api;

        $teachersId = input('post.teachersId/a');
        $token = input('post.token');

        if (!$teachersId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'delete_student');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $userData = $user->where('number', $tokenData['data']->number)
                ->where('role_id', '<>', 2)
                ->find();
            $userLevel = $userData->roleLevel->level;

            $changeUser = $user->where('id', 'in', $teachersId)
                ->select();
            foreach ($changeUser as $changeUserItem) {
                $changeUserLevel = $changeUserItem->roleLevel->level;
                if ($userLevel >= $changeUserLevel) {
                    return $api->return_msg(405, '部分角色权限等级大于或等于当前账户！');
                }
            }

            $result = $user->destroy($teachersId);
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if ($result) {
            return $api->return_msg(200, '删除成功！' . '删除了' . $result . '条数据');
        } else {
            return $api->return_msg(401, '删除失败！没有数据被删除');
        }
    }
}
?>