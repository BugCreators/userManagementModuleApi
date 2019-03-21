<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\User as UserModel;
use app\api\model\Role as RoleModel;
use app\api\model\College as CollegeModel;
use app\api\model\Major as MajorModel;
use app\api\model\VClass as ClassModel;
use think\Controller;
use think\Db;

class User extends Controller
{
     /**
     * 获取系统设置
     */
    public function getSysSetting()
    {
        $api = new Api();
        $data = ['schoolName' => '韶关学院'];
        return $api->return_msg(200, '', $data);
    }

    /**
     * 登陆接口
     * @param [array] $data
     * @param [string] $data['number'] [账户名]
     * @param [string] $data['password'] [密码]
     */
    public function login()
    {
        $api = new Api();
        // $data = input("post.");
        $data = input();
        if(!$data['number'] || !$data['password']){
            return $api->return_msg(400, '账户或密码为空！');
        };
        
        $userModel = new UserModel();
        $user = $userModel->where('number', $data['number'])
            ->find();
        if(!$user || $data['password'] !== $user['password']){
            return $api->return_msg(401, '账户或密码错误！');
        };

        $user->roleName = $user->role->name;

        $token = $api->lssue($user['number']);
        $user->token = $token;

        $user->hidden(['id', 'lastip', 'deleted', 'roleid']);
        
        return $api->return_msg(200, '', $user);
    }

    /**
     * 获取用户详情
     * @param [array] $data 
     * @param [string] $data['number'] [账户名]
     * @param [string] $data['token'] [Token]
     */
    public function getUserInfo()
    {
        // Db::listen(function($sql, $time, $explain){
        //     // 记录SQL
        //     echo $sql. ' ['.$time.'s]';
        //     // 查看性能分析结果
        //     dump($explain);
        // });

        $data = input('get.');
        $api = new Api();
        if(!$data['number']) {
            return $api->return_msg(400, '用户不存在！');
        };

        $tokenData = $api->verification($data['token']);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        } else if ($tokenData['data']->number != $data['number']) {
            return $api->return_msg(401, '参数错误！');
        }

        $userModel = new UserModel();
        $user = $userModel
            ->field('realname, number, phone, v_class_id, email, sex, address, description')
            ->where('number', $data['number'])
            ->find();

        $user->class = $user->vclass->grade . $user->vclass->name;
        $user->college = $user->vclass->major->college->name;

        $user->hidden(['v_class_id', 'vclass']);

        return $api->return_msg(200, '', $user);
    }

    /**
     * 修改个人信息
     * @param [array] $data 
     * @param [string] $data['number'] [账户名]
     * @param [string] $data['token'] [Token]
     */
    public function changeUserInfo()
    {
        $data = input('get.');
        $api = new Api();
    }
}
?>