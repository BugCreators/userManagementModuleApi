<?php
namespace app\api\controller;

use app\api\controller\Api;

class System
{
    /**
     * 获取系统设置
     * @method [GET]
     */
    public function getSysSetting()
    {
        $api = new Api;
        $path = config('systemJson');
        $string = file_get_contents($path);
        $data = json_decode($string);
        return $api->msg_200($data);
    }

    /**
     * 修改学校名字
     * @method [GET]
     * @param [string] $schoolName [学校名称]
     * @param [token] $token [Token]
     */
    public function changeSchoolName()
    {
        $api = new Api;
        $schoolName = input('get.data');
        $token = input('get.token');

        if (!$schoolName || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        $isPermission = $api->authority($tokenData['data']->number, 'update_system_setting');
        if (!$isPermission) {
            return $api->msg_405();
        }

        try {
            $path = config('systemJson');
            $string = file_get_contents($path);
            $data = json_decode($string);
            $data->schoolName = $schoolName;

            $data->operator = $tokenData['data']->number;
            $data->update_time = date('Y-m-d H:i:s', time());

            $data_json = json_encode($data);
            file_put_contents('./static/setting-back-up/setting' . time() . '.json', $data_json);
            file_put_contents('./static/sysSetting.json', $data_json);
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->return_msg(200, '修改成功！');
    }

    /**
     * 修改学校地址
     * @method [GET]
     * @param [string] $schoolAddress [学校地址]
     * @param [token] $token [Token]
     */
    public function changeSchoolAddress()
    {
        $api = new Api;
        $schoolAddress = input('get.data');
        $token = input('get.token');

        if (!$schoolAddress || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        $isPermission = $api->authority($tokenData['data']->number, 'update_system_setting');
        if (!$isPermission) {
            return $api->msg_405();
        }

        try {
            $path = config('systemJson');
            $string = file_get_contents($path);
            $data = json_decode($string);
            $data->schoolAddress = $schoolAddress;

            $data->operator = $tokenData['data']->number;
            $data->update_time = date('Y-m-d H:i:s', time());

            $data_json = json_encode($data);
            file_put_contents('./static/setting-back-up/setting' . time() . '.json', $data_json);
            file_put_contents('./static/sysSetting.json', $data_json);
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->return_msg(200, '修改成功！');
    }
}
?>