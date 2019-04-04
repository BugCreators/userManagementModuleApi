<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\User as UserModel;
use app\api\model\VClass as ClassModel;
use app\api\model\Major as MajorModel;
use app\api\model\College as CollegeModel;

class Student
{
    /**
     * 获取学生列表
     * @method [GET]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据] [0: 按学生名搜索] [1:按学院名搜索] [5:按班级名搜索]
     * @param [string] $searchValue [搜索值]
     * @param [token] $token [Token]
     */
    public function getStudentList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_student');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $list = array();

            if ($searchValue) {
                switch ($searchBasis) {
                    case '0':
                        $user = new UserModel;
                        $list = $user->where('realname', 'like', '%' . $searchValue . '%')
                            ->where('role_id', 2)
                            ->select();
                        break;
                    case '1':
                        $college = new CollegeModel;
                        $collegeList = $college->where('name', 'like', '%' . $searchValue . '%')
                            ->select();
                        if ($collegeList) {
                            foreach ($collegeList as $collegeItem) {
                                $list = array_merge($list, $collegeItem->students);
                            };
                        }
                        break;
                    case '5':
                        $class = new ClassModel;
                        $classList = $class->where('name', 'like', '%' . $searchValue . '%')
                            ->select();
                        if ($classList) {
                            foreach ($classList as $classItem) {
                                $list = array_merge($list, $classItem->students);
                            };
                        };
                        break;
                    default:
                        return $api->msg_401();
                        break;
                };
                $count = count($list);
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
            } else {
                $count = $user->where('role_id', 2)
                    ->count();
                $list = $user->field('id, realname, number, class_id, sex, phone, email, address, description')
                    ->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->where('role_id', 2)
                    ->order('id')
                    ->select();
            }
            foreach ($list as $item) {
                $item->className = $item->vclass->grade . $item->vclass->name;
                $item->collegeName = $item->vclass->major->college->name;
                $item->hidden(['vclass', 'class_id']);
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
     * 获取专业列表
     * @method [POST]
     * @param [array] $data [查询条件]
     * @param [string] $data['college_id'] [学院ID]
     * @param [string] $data['major_id'] [专业ID]
     * @param [string] $data['class_id] [班级ID]
     * @param [string] $token [Token]
     */
    public function getAllStudentList()
    {
        $api = new Api;
        $data = input('post.data/a');
        $token = input('post.token');

        if (!$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_student');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            if (!$data['college_id'] || $data['college_id'] == '-1') {
                $list = $user->field('number as 学号, realname as 姓名, sex as 性别,class_id, college_id,
                    phone as 手机号码, address as 地址, email as 邮箱, description as 个人描述')
                    ->where('role_id', 2)
                    ->select();
                foreach ($list as $item) {
                    $item->appendRelationAttr('classNameByGetAll', ['班级名']);
                    $item->appendRelationAttr('collegeNameByGetAll', ['学院名']);
                    $item->hidden(['class_id', 'college_id']);
                }
            } elseif (!$data['major_id'] || $data['major_id'] == '-1') {
                $list = $user->field('number as 学号, realname as 姓名, sex as 性别,class_id, college_id,
                    phone as 手机号码, address as 地址, email as 邮箱, description as 个人描述')
                    ->where('role_id', 2)
                    ->where('college_id', $data['college_id'])
                    ->select();
                foreach ($list as $item) {
                    $item->appendRelationAttr('classNameByGetAll', ['班级名']);
                    $item->appendRelationAttr('collegeNameByGetAll', ['学院名']);
                    $item->hidden(['class_id', 'college_id']);
                }
            } elseif (!$data['class_id'] || $data['class_id'] == '-1') {
                $major = new MajorModel;
                $majorData = $major->where('id', $data['major_id'])
                    ->find();
                $classList = $majorData->vclass;
                $list = array();
                foreach ($classList as $classItem) {
                    $list = array_merge($list, $classItem->getStudentByGetAll);
                }
                if ($list) {
                    foreach ($list as $studentItem) {
                        $studentItem['班级名'] = $studentItem->vclass->grade . $studentItem->vclass->name;
                        $studentItem->appendRelationAttr('collegeNameByGetAll', ['学院名']);
                        $studentItem->hidden(['class_id', 'college_id', 'vclass']);
                    }
                }
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200($list);
    }

    /**
     * 获取学生详情
     * @method [GET]
     * @param [string] $studentId [学生ID]
     * @param [string] $token [Token]
     */
    public function getStudentDetail()
    {
        $api = new Api;
        $studentId = input('get.id');
        $token = input('get.token');
        
        if (!$studentId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_student');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $data = $user->field('id, realname, number, class_id, college_id, sex, phone, email, address, description')
                ->where('id', $studentId)
                ->find();
            $data->major_id = $data->vclass->major_id;
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
     * 重置学生密码
     * @method [GET]
     * @param [int] $studentId [学生ID]
     * @param [token] $token [Token]
     */
    public function resetPassword()
    {
        $api = new Api;
        $studentId = input('get.id');
        $token = input('get.token');

        if (!$studentId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        }

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_student');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $userModel = new UserModel;
            $user = $userModel
                ->where('id', $studentId)
                ->find();
            $result = $user->save(['password' => md5($user->number)], ['id' => $studentId]);

        } catch (\Exception $e) {
            return $api->msg_500();
        }
        if ($result) {
            return $api->return_msg(200, "重置成功！");
        } else {
            return $api->return_msg(401, '重置失败，数据未改动！');
        }
    }

    /**
     * 编辑学生信息
     * @method [POST]
     * @param [array] $data [学生详情]
     * @param [string] $data['id'] [学生ID]
     * @param [string] $data['realname'] [学生名]
     * @param [string] $data['number'] [学号]
     * @param [string] $data['sex'] [性别]
     * @param [string] $data['college_id'] [学院ID]
     * @param [string] $data['class_id'] [班级ID]
     * @param [string] $data['phone'] [手机]
     * @param [string] $data['address'] [地址]
     * @param [string] $data['email'] ['邮箱']
     * @param [string] $data['description'] [个人描述]
     * @param [string] $token [Token]
     */
    public function changeStudent()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['id'] || !$data['realname'] || strlen($data['number']) != 11 || !$data['college_id'] || !$data['class_id']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_student');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $result = $user->allowField(['realname', 'number', 'sex', 'college_id',
                'class_id', 'description', 'phone', 'address', 'email'])
                ->save($data, ['id' => $data['id']]);

            if ($result) {
                return $api->return_msg(200, '修改成功！');
            } else {
                return $api->return_msg(401, '修改失败，数据未改动！');
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }
    }

    /**
     * 添加学生
     * @method [POST]
     * @param [array] $data [学生详情]
     * @param [string] $data['realname'] [学生名]
     * @param [string] $data['number'] [学号]
     * @param [string] $data['sex'] [性别]
     * @param [string] $data['college_id'] [学院ID]
     * @param [string] $data['class_id'] [班级ID]
     * @param [string] $data['phone'] [手机]
     * @param [string] $data['address'] [地址]
     * @param [string] $data['email'] ['邮箱']
     * @param [string] $data['description'] [个人描述]
     * @param [string] $token [Token]
     */
    public function addStudent()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['realname'] || strlen($data['number']) != 11 || !$data['college_id'] || !$data['class_id']) {
            return $api->msg_401();
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

            $user = new UserModel;
            $haveExisted = $user->where('number', $data['number'])
                ->find();
            if($haveExisted) {
                return $api->return_msg(401, '学号已存在！');
            }

            $data['role_id'] = 2;
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
     * 批量添加学生
     * @method [POST]
     * @param [string] $data [添加条件]
     * @param [string] $data['college_id'] [学院ID]
     * @param [string] $data['major_id'] [专业ID]
     * @param [string] $data['class_Id'] [班级ID]
     * @param [array] $studentList [学院列表]
     * @param [string] $token [Token]
     */
    public function importStudentList()
    {
        $api = new Api;

        $data = input('post.data/a');
        $studentList = input('post.studentList/a');
        $token = input('post.token');

        if (!$studentList || !$token || !$data['college_id'] || !$data['class_id']) {
            return $api->msg_401();
        }
        
        foreach ($studentList as $studentItem) {
            if (!$studentItem['realname'] || strlen($studentItem['number']) != 11) {
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

            $class = new ClassModel;
            $classData = $class->where('id', $data['class_id'])
                ->find();
            $collegeIdOfClass = $classData->major->college_id;
            if (!$classData || $collegeIdOfClass != $data['college_id']) {
                return $api->msg_401();
            }

            $numberOfData = array_map(function($item) {
                return $item['number'];
            }, $studentList);
            $user = new UserModel;
            $numberOfDatabase = $user->where('role_id', 2)->column('number');
            $allNumberList = array_merge($numberOfData, $numberOfDatabase);
            if (count($allNumberList) != count(array_unique($allNumberList))) {
                return $api->return_msg(401, '导入失败！部分学号已存在');
            };

            foreach ($studentList as $key => $value) {
                $studentList[$key]['password'] = md5($studentList[$key]['number']);
            }

            $result = $user->allowField(true)
                ->saveAll($studentList);
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
     * 删除学生
     * @method [POST]
     * @param [string] $studentsId [学院ID]
     * @param [string] $token [Token]
     */
    public function deleteStudent()
    {
        $api = new Api;

        $studentsId = input('post.studentsId/a');
        $token = input('post.token');

        if (!$studentsId || !$token) {
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
            $result = $user->destroy($studentsId);
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