<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\Major as MajorModel;
use app\api\model\User as UserModel;

class Major
{
    /****************后台接口 BEGIN*******************/
    /**
     * 获取专业列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $majorName [专业名]
     * @param [string] $token [Token]
     */
    public function getMajorList()
    {
        $api = new Api;
        $pageSize = input('post.pageSize');
        $pageIndex = input('post.pageIndex');
        $majorName = input('post.searchValue.name');
        $token = input('post.token');

        if (!$pageSize || !$pageIndex || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_major');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $major = new MajorModel;
            if ($majorName) {
                $list = $major->where('name', 'like', $majorName . '%')
                ->select();
                $count = count($list);
            } else {
                $count = $major->count();
                $list = $major
                    ->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->order('id')
                    ->select();
            }

            foreach($list as $item) {
                $item->hidden(['college_id']);
                $item->college;
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
     * @param [string] $token [Token]
     */
    public function getAllMajorList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_major');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $major = new MajorModel;
            $count = $major->count();
            $list = $major->field('name as 专业名, level as 学历层次,
                college_id, description as 专业概况, train_objective as 培养目标,
                main_course as 主要课程, employment_direction as 就业方向')
                ->select();
            foreach($list as $item) {
                $item->hidden(['college_id']);
                $item->appendRelationAttr('collegeName', ['学院名']);
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200($list);
    }

    /**
     * 获取专业详情
     * @method [GET]
     * @param [int] $marjorId [学院ID]
     * @param [string] $token [Token]
     */
    public function getMajorDetail() 
    {
        $api = new Api;
        $marjorId = input('get.id');
        $token = input('get.token');
        
        if (!$marjorId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_major');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $major = new MajorModel;
            $data = $major->where('id', $marjorId)
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
     * 获取院徽
     * @method [GET]
     * @param [int] $collegeId [学院ID]
     * @param [string] $token [Token]
     */
    public function getCollegeLogo()
    {
        $api = new Api;
        $collegeId = input('get.id');
        $token = input('get.token');
        
        if (!$collegeId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $college = new CollegeModel;
            $data = $college->field('logo as url')
                ->where('id', $collegeId)
                ->find();
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200($data);
    }

    /**
     * 上传院徽
     * @method [POST]
     * @param [int] $logoFile [LOGO文件]
     * @param [string] $collegeId [学院ID]
     * @param [string] $token [Token]
     */
    public function changeCollegeLogo()
    {
        $api = new Api;
        $logoFile = request()->file('image');
        $collegeId = input('post.id');
        $token = input('post.token');

        if (!$collegeId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $info = $logoFile->validate([
                'size' => 1024 * 1024 * 2,
                'ext' => 'jpg,png'
                ])->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'collegeLogo');
            if($info){
                $college = new CollegeModel;
                $url = str_replace("\\", "/", DS . 'static' . DS . 'collegeLogo' . DS . $info->getSaveName());
                $result = $college->save([
                    'logo' => $url
                ], ['id' => $collegeId]);
            }else{
                return $api->return_msg(401, $logoFile->getError());
            }

        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if ($result) {
            return $api->return_msg(200, '上传成功！', [
                'url' => $url,
                'id' => $collegeId
            ]);
        } else {
            return $api->return_msg(401, '上传失败！');
        }
    }

    /**
     * 删除院徽
     * @method [GET]
     * @param [string] $collegeId [学院ID]
     * @param [string] $token [Token]
     */
    public function deleteCollegeLogo()
    {
        $api = new Api;
        $collegeId = input('post.id');
        $token = input('post.token');

        if (!$collegeId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $college = new CollegeModel;
            $result = $college->save(['logo' => ""], ['id' => $collegeId]);

        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if ($result) {
            return $api->return_msg(200, '删除成功！', $collegeId);
        } else {
            return $api->return_msg(401, '删除失败！');
        }
        
    }

    /**
     * 编辑学院信息
     * @method [POST]
     * @param [array] $data [学院详情]
     * @param [string] $data['id'] [学院ID]
     * @param [string] $data['name'] [学院名]
     * @param [string] $data['englishName'] [英文名]
     * @param [string] $data['website'] [学院官网]
     * @param [string] $data['description'] [学院描述]
     * @param [string] $token [Token]
     */
    public function changeCollegeDetail()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['id'] || !$data['name']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $college = new CollegeModel;
            $result = $college->allowField(['name', 'en_name', 'website', 'description'])
                ->save($data, ['id' => $data['id']]);

            if ($result) {
                $newData = $college->where('id', $data['id'])
                    ->find();
                return $api->return_msg(200, '修改成功！', $newData);
            } else {
                return $api->return_msg(401, '修改失败，数据未改动！');
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }
    }

    /**
     * 添加学院
     * @method [POST]
     * @param [array] $data [学院详情]
     * @param [string] $data['name'] [学院名]
     * @param [string] $data['englishName'] [英文名]
     * @param [string] $data['website'] [学院官网]
     * @param [string] $data['description'] [学院描述]
     * @param [string] $token [Token]
     */
    public function addCollege()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['name']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $college = new CollegeModel;
            $haveExisted = $college->where('name', $data['name'])
                ->find();
            if($haveExisted) {
                return $api->return_msg(401, '该学院已存在！请输入其它学院');
            }

            $result = $college->allowField(true)
                ->save($data);
            
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if ($result) {
            return $api->return_msg(200, '添加成功！');
        } else {
            return $api->return_msg(401);
        }
    }

    /**
     * 批量添加学院
     * @method [POST]
     * @param [array] $collegeList [学院列表]
     * @param [string] $token [Token]
     */
    public function importCollegeList()
    {
        $api = new Api;

        $collegeList = input('post.collegeList/a');
        $token = input('post.token');

        if (!$collegeList || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $nameListOfData = array_map(function($item) {
                return $item['name'];
            }, $collegeList);
            $college = new CollegeModel;
            $nameListOfDataBase = $college->column('name');
            $allNameList = array_merge($nameListOfData, $nameListOfDataBase);
            if (count($allNameList) != count(array_unique($allNameList))) {
                return $api->return_msg(401, '导入失败！部分学院名已存在');
            };

            $result = $college->allowField(true)
                ->saveAll($collegeList);
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
     * 删除学院
     * @method [POST]
     * @param [string] $collegeId [学院ID]
     * @param [string] $token [Token]
     */
    public function deleteCollege()
    {
        $api = new Api;

        $collegeId = input('post.collegesId/a');
        $token = input('post.token');

        if (!$collegeId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'delete_college');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $college = new CollegeModel;

            $result = $college->destroy($collegeId);
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        if ($result) {
            return $api->return_msg(200, '删除成功！' . '删除了' . $result . '条数据');
        } else {
            return $api->return_msg(401, '删除失败！没有数据被删除');
        }
    }
    /******************** END ***********************/
}
?>