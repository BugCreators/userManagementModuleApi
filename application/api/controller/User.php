<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\User as UserModel;
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
        return $api->return_msg(200, '', $data);
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
        $user = $userModel->where('number', $number)
            ->find();
        if (!$user || $password !== $user['password']) {
            return $api->return_msg(401, '账户或密码错误！');
        };
        if ($user['deleted']) {
            return $api->return_msg(402, '该用户被冻结，请联系管理员！');
        }

        $user->roleName = $user->role->name;

        $token = $api->lssue($user['number']);
        $user->token = $token;

        $user->hidden([
            'id',
            'lastip',
            'deleted',
            'v_class_id',
            'role_id',
            'role',
            'username',
            'url'
        ]);
        
        return $api->return_msg(200, '', $user);
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
        $number = input('post.number');
        $token = input('post.token');
        
        if (!$number || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        } elseif ($tokenData['data']->number != $number) {
            return $api->msg_401();
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

        return $api->return_msg(200, '', $user);
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

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        } elseif ($tokenData['data']->number != $number) {
            return $api->msg_401();
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
            return $api->return_msg(500, '系统出错，请稍后重试！');
        };
        if ($result) {
            return $api->return_msg(200, '更新成功！');
        } else {
            return $api->return_msg(401, '更新失败，数据未改动！');
        }
    }
}
?>