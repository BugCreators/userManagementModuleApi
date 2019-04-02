<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\User as UserModel;
use app\api\model\VClass as ClassModel;
use app\api\model\College as CollegeModel;
use think\Controller;

class User extends Controller
{
     /**
     * 获取系统设置
     * @method [GET]
     */
    public function getSysSetting()
    {
        $api = new Api;
        $data = ['schoolName' => '韶关学院'];
        return $api->msg_200($data);
    }

    /**
     * 获取年级年份
     * @method [GET]
     */
    public function getGradeList()
    {
        $api = new Api;
        $years = array();
        $currentYear = date('Y');
        for ($i = 0; $i < 7; $i++) {
            $years[$i] = $currentYear - $i . "级";
        }

        return $api->msg_200($years);
    }

    /**
     * 登陆接口
     * @method [POST]
     * @param [array] $data
     * @param [int] $number [账户名/学号/工号]
     * @param [string] $password [密码]
     */
    public function login()
    {
        $api = new Api;
        $number = input('number');
        $password = input('password');

        if (!$number || !$password) {
            return $api->msg_401();
        }
        
        $userModel = new UserModel;
        $user = $userModel->field('number, realname, password, deleted, role_id')
            ->where('number', $number)
            ->find();
        if (!$user || $password !== $user['password']) {
            return $api->return_msg(401, '账户或密码错误！');
        };
        if ($user['deleted']) {
            return $api->return_msg(402, '该用户被冻结，请联系管理员！');
        }

        $time = time();
        $user->save(['last_login_time' => $time]);

        $user->roleName = $user->role->name;

        $user->intoBackstage = $user->role
            ->authorityByName('into_backstage')
            ->pivot->permission;

        $token = $api->lssue($user['number'], $time);
        $user->token = $token;

        $user->hidden([
            'deleted',
            'role_id',
            'role'
        ]);

        return $api->msg_200($user);
    }

    /**
     * 获取用户详情
     * @method [POST]
     * @param [array] $data 
     * @param [int] $number [账户名/学号/工号]
     * @param [string] $token [Token]
     */
    public function getUserInfo()
    {
        // Db::listen(function($sql, $time, $explain){
        //     // 记录SQL
        //     echo $sql. ' ['.$time.'s]';
        //     // 查看性能分析结果
        //     dump($explain);
        // });

        $api = new Api;
        $number = input('number');
        $token = input('token');
        
        if (!$number || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token, $number);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        }

        $userModel = new UserModel;
        $user = $userModel
            ->field('realname, number, phone, v_class_id, email, sex, address, description')
            ->where('number', $number)
            ->find();

        if ($user['v_class_id']) {
            $user->class = $user->vclass->grade . $user->vclass->name;
            $user->college = $user->vclass->major->college->name;
        }
        
        $user->hidden(['v_class_id', 'vclass']);

        return $api->msg_200($user);
    }

    /**
     * 个人信息页修改信息
     * @method [POST]
     * @param [array] $data 
     * @param [int] $number [账户名/学号/工号]
     * @param [string] $token [Token]
     * @param [string] $email [邮箱]
     * @param [string] $sex [性别]
     * @param [string] $address [地址]
     * @param [string] $description [个人描述]
     */
    public function changeUserInfoByUser()
    {
        $api = new Api;
        $number = input('post.number');
        $email = input('post.email');
        $sex = input('post.sex');
        $address = input('post.address');
        $description = input('post.description');
        $token = input('post.token');

        if (!$number || !$email || $sex == '' || !$address || !$description || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token, $number);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        }

        try {
            $userModel = new UserModel;
            $result = $userModel
                ->save([
                    'email' => $email,
                    'sex' => (int)$sex,
                    'address' => $address,
                    'description' => $description
                ], ['number' => $number]);
        } catch (\Exception $e) {
            return $api->msg_500();
        };
        if ($result) {
            return $api->return_msg(200, '修改成功！');
        } else {
            return $api->return_msg(401, '修改失败，数据未改动！');
        }
    }

    /**
     * 用户修改密码
     * @method [POST]
     * @param [string] $oldPw [旧密码]
     * @param [string] $newPw [新密码]
     * @param [string] $confirmPw [确认密码]
     * @param [string] $number [账号]
     * @param [string] $token [Token]
     */
    public function changePasswordByUser()
    {
        $api = new Api;
        $oldPw = input('post.oldPw');
        $newPw = input('post.newPw');
        $confirmPw = input('post.confirmPw');
        $number = input('post.number');
        $token = input('post.token');

        if (!$oldPw || !$newPw || !$confirmPw || $newPw != $confirmPw || !$number || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token, $number);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        }

        try {
            $userModel = new UserModel;
            $user = $userModel
                ->where('number', $number)
                ->find();
            if ($oldPw !== $user['password']) {
                return $api->return_msg(401, "密码错误！");
            };
            $result = $user->save([
                'password' => $newPw
            ]);
        } catch (\Exception $e) {
            return $api->msg_500();
        }
        if ($result) {
            return $api->return_msg(200, "修改成功！请重新登录！");
        } else {
            return $api->return_msg(401, '修改失败，数据未改动！');
        }
    }

    /**
     * 获取进入后台权限
     * @method [GET]
     * @param [string] $token [Token]
     */
    public function getIntoBackstage()
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
            $userModel = new UserModel;
            $user = $userModel->field('role_id')
                ->where('number', $tokenData['data']->number)
                ->find();
            $user->intoBackstage = $user->role
                ->authorityByName("into_backstage")
                ->pivot->permission;
        } catch (\Exception $e) {
            return $api->msg_500();
        };

        $user->hidden(['role', 'role_id']);

        return $api->msg_200($user);
    }

    /**
     * 获取查询权限
     * @method [GET]
     * @param [string] $token [Token]
     */
    public function getSelectAuthority()
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
            $userModel = new UserModel;
            $user = $userModel->field('role_id')
                ->where('number', $tokenData['data']->number)
                ->find();
            $selectAuthority = $user->role->authority;
        } catch (\Exception $e) {
            return $api->msg_500();
        };

        $data = [];
        foreach($selectAuthority as $authority) {
            if(strpos($authority->name, 'select_') === 0) {
                $data[$authority->name] = $authority->pivot->permission;
            }
        }

        return $api->msg_200($data);
    }

    /****************后台接口 BEGIN*******************/
    /**
     * 获取用户列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $moduleName [模块名]
     * @param [string] $searchBasis [搜索依据]  [0: 按用户名字搜索] [1:按班级名搜索学生] [2:按学院名搜索学生] [3:按学院名搜索教师]
     * @param [string] $searchValue [搜索值]
     * @param [token] $token [Token]
     */
    public function getUserList()
    {
        $api = new Api;
        $pageSize = input('post.pageSize');
        $pageIndex = input('post.pageIndex');
        $moduleName = input('post.moduleName');
        $searchBasis = input('post.searchBasis');
        $searchValue = input('post.searchValue.name');
        $token = input('post.token');

        if (!$pageSize || !$pageIndex || !$moduleName || !$token) {
            return $api->msg_401();
        }

        if ($moduleName == 'student') {
            $roleId = 1;
        } elseif ($moduleName =='teacher') {
            $roleId = 2;
        } else {
            return $api->return_msg(401, '该模块不存在！');
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_' . $moduleName);
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $list = array();

            if ($searchBasis && $searchValue) {
                switch ($searchBasis) {
                    default:
                    case '0':
                        $user = new UserModel;

                        $list = $user->where('name', 'like', $searchValue . '%')
                            ->where('role_id', $roleId)
                            ->select();
                        break;
                    case '1':
                        $class = new ClassModel;

                        $classList = $class->where('name', 'like', $searchValue . '%')
                            ->select();
                        if ($classList) {
                            foreach ($classList as $item) {
                                $list = array_merge($list, $item->user()->where('role_id', 2));
                            };
                        };
                        break;
                    case '2':
                        $college = new CollegeModel;
                        $classList = array();

                        $collegeList = $college->where('name', 'like', $searchValue . '%')
                            ->select();
                        if ($collegeList) {
                            foreach ($collegeList as $item) {
                                $classList = array_merge($classList, $item->class);
                            };
                            if ($classList) {
                                foreach($classList as $item) {
                                    $list = array_merge($list, $item->user()->where('role_id', 2));
                                };
                            };
                        };
                        break;
                    case '3':
                        $college = new CollegeModel;

                        $collegeList = $college->where('name', 'like', $searchValue . '%')
                            ->select();
                        if ($collegeList) {
                            foreach ($collegeList as $item) {
                                $list = array_merge($list, $item->user()->where('role_id', '<>', 2));
                            };
                        };
                };
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
                $count = count($list);
            } else {
                $count = $user->where('role_id', 2)
                    ->count();
                $list = $user->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->where('role_id', 2)
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
}
?>