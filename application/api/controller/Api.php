<?php
namespace app\api\controller;

use \Firebase\JWT\JWT;

class Api
{
    /**
     * 签发 Token
     * @param  [array] $data [用户信息]
     */
    public function lssue($number)
    {
        if (!$number) {
            return $this->return_msg(400, '参数错误！');
        }
        $key = 'avue@777';
        $time = time();
        $token = [
            'iss' => 'http://api.avue.com',  //签发者 可选
           	'iat' => $time,                  //签发时间
           	'exp' => $time + 3600 * 10,      //过期时间,这里设置10个小时
            'data' => [                      //自定义信息，不要定义敏感信息
                'number' => $number
            ]
        ];
        return JWT::encode($token, $key);    //输出Token
    }

    /**
     * 解析 Token
     * @param  [string] $jwt [签发的token]
     */
    public function verification($jwt)
    {
        $key = 'avue@777';                                    //key要和签发的时候一样

		try {
            JWT::$leeway = 60;                                //当前时间减去60，把时间留点余地
            $decoded = JWT::decode($jwt, $key, ['HS256']);    //HS256方式，这里要和签发的时候对应
        } catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
            return $this->return_msg(401, '参数错误！');
        } catch (\DomainException $e) {                      //其他错误
            return $this->return_msg(401, '参数错误！');
        }catch (\Firebase\JWT\ExpiredException $e) {            // token过期
            return $this->return_msg(402, '会话已过期，请重新登陆！');
        } catch (\Exception $e) {                                 //其他错误
            return $this->return_msg(500, '系统错误，请联系管理员！');
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
     * 400: 没有接收到参数
     * 401: 参数在数据库中不存在
     * 402: token过期
     * 403: 
     * 404：
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
}
?>