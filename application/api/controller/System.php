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

    /**
     * 删除轮播图图片
     * @method [GET]
     * @param [string] $index [图片Index]
     * @param [string] $token [Token]
     */
    public function deleteCarouselItem()
    {
        $api = new Api;
        $index = input('get.index');
        $token = input('get.token');

        if (!$index || !$token) {
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
            
            foreach($data->carousel as $key => $item) {
                if ($item->index == $index) {
                    array_splice($data->carousel, $key, 1); 
                    break;
                }
            }

            $data->operator = $tokenData['data']->number;
            $data->update_time = date('Y-m-d H:i:s', time());

            $data_json = json_encode($data);
            file_put_contents('./static/setting-back-up/setting' . time() . '.json', $data_json);
            file_put_contents('./static/sysSetting.json', $data_json);

        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->return_msg(200, '删除成功！');
    }

    /**
     * 获取轮播图图片信息
     * @method [GET]
     * @param [string] $index [图片Index]
     * @param [string] $token [Token]
     */
    public function getCarouselItem()
    {
        $api = new Api;
        $index = input('get.index');
        $token = input('get.token');

        if (!$index || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        $isPermission = $api->authority($tokenData['data']->number, 'select_system_setting');
        if (!$isPermission) {
            return $api->msg_405();
        }

        try {
            $path = config('systemJson');
            $string = file_get_contents($path);
            $data = json_decode($string);
            
            foreach($data->carousel as $item) {
                if ($item->index == $index) {
                    $return_data = $item;
                    break;
                }
            }

        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if ($return_data) {
            return $api->return_msg(200, '', $return_data);
        } else {
            return $api->msg_401();
        }   
    }

    /**
     * 添加轮播图图片
     * @method [POST]
     * @param [string] $website [链接]
     * @param [file] $picture [图片文件]
     * @param [string] $token [Token]
     */
    public function addCarouselItem()
    {
        $api = new Api;
        $picture = request()->file('image');
        $website = input('post.website');
        $token = input('post.token');

        if (!$token) {
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
            $carousel = $data->carousel;

            if (count($carousel) >= 6) {
                return $api->return_msg(401, '不能有更多的轮播图！');
            }

            $info = $picture->validate([
                'size' => 1024 * 1024 * 2,
                'ext' => 'jpg,png'
                ])->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'carousel');
            if($info){
                $url = str_replace('\\', '/', DS . 'static' . DS . 'carousel' . DS . $info->getSaveName());
            }else{
                return $api->return_msg(401, $picture->getError());
            }

            $carouselItem = [
                "index" => (string)time(),
                "href" => $website,
                'url' => $url
            ];

            $carousel[] = json_decode(json_encode($carouselItem));
            $data->carousel = $carousel;
            
            $data->operator = $tokenData['data']->number;
            $data->update_time = date('Y-m-d H:i:s', time());

            $data_json = json_encode($data);
            file_put_contents('./static/setting-back-up/setting' . time() . '.json', $data_json);
            file_put_contents('./static/sysSetting.json', $data_json);

        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->return_msg(200, '添加成功！');
    }

    /**
     * 编辑轮播图图片
     * @method [POST]
     * @param [string] $index [图片Index]
     * @param [file] $picture [图片文件]
     * @param [string] $token [Token]
     */
    public function changeCarouselItemPicture()
    {
        $api = new Api;
        $picture = request()->file('image');
        $index = input('post.index');
        $token = input('post.token');

        if (!$token) {
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
            
            foreach($data->carousel as $item) {
                if ($item->index == $index) {
                    $info = $picture->validate([
                        'size' => 1024 * 1024 * 2,
                        'ext' => 'jpg,png'
                        ])->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'carousel');
                    if ($info) {
                        $url = str_replace('\\', '/', DS . 'static' . DS . 'carousel' . DS . $info->getSaveName());
                    } else {
                        return $api->return_msg(401, $picture->getError());
                    };
                    $item->url = $url;
                    break;
                }
            }

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
     * 编辑轮播图链接
     * @method [GET]
     * @param [string] $index [Index]
     * @param [string] $website [链接]
     * @param [string] $token [Token]
     */
    public function changeCarouselItemWebsite()
    {
        $api = new Api;
        $index = input('get.index');
        $website = input('get.website');
        $token = input('get.token');

        if (!$token) {
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
            
            foreach($data->carousel as $item) {
                if ($item->index == $index) {
                    $item->href = $website;
                    break;
                }
            }

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