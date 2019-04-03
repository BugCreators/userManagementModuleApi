<?php
namespace app\api\controller;

use \Firebase\JWT\JWT;
use app\api\model\User as UserModel;

class Api
{
    /**
     * 签发 Token
     * @param  [array] $data [用户信息]
     */
    public function lssue($number, $time)
    {
        if (!$number) {
            return $this->msg_401();
        }
        $key = 'avue@777';
        $time = time();
        $token = [
            'iss' => 'http://api.avue.com',  //签发者 可选
           	'iat' => $time,                  //签发时间
           	'exp' => $time + 3600 * 10,      //过期时间,这里设置10个小时
            'data' => [                      //自定义信息，不要定义敏感信息
                'number' => $number,
                'last_login_time' => $time
            ]
        ];
        return JWT::encode($token, $key);    //输出Token
    }

    /**
     * 解析 Token
     * @param  [string] $jwt [签发的token]
     */
    public function verification($jwt, $number = null)
    {
        $key = 'avue@777';                                    //key要和签发的时候一样

		try {
            JWT::$leeway = 60;                                //当前时间减去60，把时间留点余地
            $decoded = JWT::decode($jwt, $key, ['HS256']);    //HS256方式，这里要和签发的时候对应
        } 
        // catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
        //     return $this->return_msg(401, '参数错误！' . $e->getMessage());
        // } catch (\DomainException $e) {                      //其他错误
        //     return $this->return_msg(401, '参数错误！' . $e->getMessage());
        // }
        catch (\Firebase\JWT\ExpiredException $e) {            // token过期
            return $this->return_msg(402, '会话已过期，请重新登陆！');
        } catch (\Exception $e) {                                 //其他错误
            return $this->msg_401();
        }

        $data = $decoded->data;

        if ($number != null && $data->number != $number) {
            return $this->msg_401();
        }

        $userModel = new UserModel;
        $user = $userModel->field('number, last_login_time')
            ->where('number', $data->number)
            ->find();
        if ($data->number != $data->number) {
            return $this->msg_401(401, '该用户不存在！');
        }
        if ($data->last_login_time != $user->last_login_time) {
            return $this->return_msg(402, '会话已过期，请重新登陆！');
        }

        return $this->return_msg(200, '', $decoded->data);
    }

    /**
    * api 数据返回
    * @param  [int] $code [结果码 200:正常/4**数据问题/5**服务器问题]
    * @param  [string] $msg  [接口要返回的提示信息]
    * @param  [array]  $data [接口要返回的数据]
    * @return [string]       [最终的json数据]
    */
    /**
     * 结果码
     * 401: 参数错误
     * 402: token过期
     * 403: 用户被冻结
     * 404：
     * 405: 没有权限
     */
    public function return_msg($code, $msg = '', $data = [])
    {
        /*********** 组合数据  ***********/
        $return_data['code'] = $code;
        $return_data['msg']  = $msg;
        $return_data['data'] = $data;
        /*********** 返回信息并终止脚本  ***********/
        return $return_data;
        die;
    }

    public function msg_200($data)
    {
        return $this->return_msg(200, '', $data);
    }

    public function msg_401()
    {
        return $this->return_msg(401, '参数错误！');
    }

    public function msg_405()
    {
        return $this->return_msg(405, '当前用户无此权限！');
    }

    public function msg_500() 
    {
        return $this->return_msg(500, '系统出错，请稍后重试！');
    }

    /**
     * 判断是否具有权限
     * @param [string] $number [用户账号]
     * @param [string] $authorityName [权限名]
     */
    public function authority($number, $authorityName)
    {
        $userModel = new UserModel;
        $user = $userModel->field('role_id')
            ->where('number', $number)
            ->find();
        $authority = $user->role
            ->authorityByName($authorityName);
        if ($authority) {
            return true;
        } else {
            return false;
        }
    }

    function merge_obj(){
        foreach(func_get_args() as $a){
            $objects[]=(array)$a;
        }
        return (object)call_user_func_array('array_merge', $objects);
    }
}
?>