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
     * 修改学校信息
     * @method [GET]
     * @param [string] $schoolInfo [学校信息]
     * @param [string] $schoolInfo['name'] [学校名字]
     * @param [string] $schoolInfo['address'] [学校地址]
     * @param [token] $token [Token]
     */
    public function changeSchoolInfo()
    {
        $api = new Api;
        $schoolInfo = input('get.schoolInfo');
        $token = input('get.token');

        $schoolInfo = json_decode($schoolInfo);

        if (!$schoolInfo->name || !$token) {
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
            $data->schoolInfo->name = $schoolInfo->name;
            $data->schoolInfo->address = $schoolInfo->address;

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
     * 添加系统链接
     * @method [GET]
     * @param [string] $system [系统信息]
     * @param [string] $system['name] [系统名]
     * @param [string] $system['website'] [系统链接]
     * @param [string] $token [Token]
     */
    public function addSystemItem()
    {
        $api = new Api;
        $system = input('get.system');
        $token = input('get.token');

        $system = json_decode($system);

        if (!$system->name || !$system->website || !$token) {
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
            $systemList = $data->systemWebsite;

            $systemItem = [
                "index" => (string)time(),
                "name" => $system->name,
                'website' => $system->website
            ];

            $systemList[] = json_decode(json_encode($systemItem));
            $data->systemWebsite = $systemList;
            
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
     * 删除系统链接
     * @method [GET]
     * @param [string] $index [系统Index]
     * @param [string] $token [Token]
     */
    public function deleteSystemItem()
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
            
            foreach($data->systemWebsite as $key => $item) {
                if ($item->index == $index) {
                    array_splice($data->systemWebsite, $key, 1); 
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
     * 编辑系统链接
     * @method [GET]
     * @param [string] $index [系统Index]
     * @param [string] $system [系统信息]
     * @param [string] $system['name] [系统名]
     * @param [string] $system['website'] [系统链接]
     * @param [string] $token [Token]
     */
    public function changeSystemItem()
    {
        $api = new Api;
        $system = input('get.system');
        $token = input('get.token');

        $system = json_decode($system);

        if (!$system->index || !$system->name || !$system->website || !$token) {
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
            
            foreach($data->systemWebsite as $item) {
                if ($system->index == $item->index) {
                    $item->name = $system->name;
                    $item->website = $system->website;
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