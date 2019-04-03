<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\VClass as ClassModel;
use app\api\model\Major as MajorModel;
use app\api\model\College as CollegeModel;

class Vclass
{
    /****************后台接口 BEGIN*******************/
    /**
     * 获取班级列表
     * @method [POST]
     * @param [int] $pageSize [页码]
     * @param [int] $pageIndex [页数]
     * @param [string] $searchBasis [搜索依据] [0:按名字搜索] [1:按学院名搜索] [2:按年级搜索]
     * @param [string] $searchValue [搜索值]
     * @param [string] $token [Token]
     */
    public function getClassList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_class');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $class = new ClassModel;
            if ($searchValue) {
                if ($searchBasis == '0') {
                    $list = $class->where('name', 'like', '%' . $searchValue . '%')
                        ->select();
                    foreach($list as $item) {
                        $item->majorName = $item->major->name;
                        $item->collegeName = $item->major->college->name;
                        $item->hidden(['major_id', 'major']);
                    };
                } elseif ($searchBasis == '1') {
                    $college = new CollegeModel;
                    $collegeList = $college->field('id, name')
                        ->where('name', 'like', '%' . $searchValue . '%')
                        ->select();
                    $list = array();
                    foreach ($collegeList as $collegeItem) {
                        $majorList = $collegeItem->major;
                        foreach ($majorList as $majorItem) {
                            $classList = $majorItem->vclass;
                            foreach ($classList as $classItem) {
                                $classItem->majorName = $majorItem->name;
                                $classItem->collegeName = $collegeItem->name;
                            }
                            $temp = json_decode(json_encode($classList), true);
                            $list = array_merge($list, $temp);
                        }
                    }
                } elseif ($searchBasis == '2') {
                    $list = $class->where('grade', 'like', '%' . $searchValue . '%')
                        ->select();
                    foreach($list as $item) {
                        $item->majorName = $item->major->name;
                        $item->collegeName = $item->major->college->name;
                        $item->hidden(['major_id', 'major']);
                    };
                } else {
                    return $api->msg_401();
                };
                $count = count($list);
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
            } else {
                $count = $class->count();
                $list = $class
                    ->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->order('id')
                    ->select();
                foreach($list as $item) {
                    $item->majorName = $item->major->name;
                    $item->collegeName = $item->major->college->name;
                    $item->hidden(['major_id', 'major']);
                }
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
     * 获取班级列表
     * @method [POST]
     * @param [string] $token [Token]
     */
    public function getAllClassList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_class');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $class = new ClassModel;
            $classList = $class->field('grade as 年级, name as 班级名, major_id')
                ->select();
            $list = array();
            $i = 0;
            foreach ($classList as $classItem) {
                $collegeName = json_decode(json_encode($classItem->major->collegeNameByGetAll), true);
                $classItem->hidden(['major', 'major_id']);
                $classItem->appendRelationAttr('majorNameByGetAll', ['专业名']);
                $temp = array_merge(json_decode(json_encode($classItem), true), $collegeName);
                $list[$i] = $temp;
                $i++;
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200($list);
    }

    /**
     * 获取班级详情
     * @method [GET]
     * @param [string] $classId [班级ID]
     * @param [string] $token [Token]
     */
    public function getClassDetail() 
    {
        $api = new Api;
        $classId = input('get.id');
        $token = input('get.token');
        
        if (!$classId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_class');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $class = new ClassModel;
            $data = $class->where('id', $classId)
                ->find();
            $data->college_id = $data->major->college->id;
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
     * 编辑班级信息
     * @method [POST]
     * @param [array] $data [班级详情]
     * @param [string] $data['id'] [班级ID]
     * @param [string] $data['grade'] [年级]
     * @param [string] $data['name'] [班级名]
     * @param [string] $data['major_id'] [专业ID]
     * @param [string] $token [Token]
     */
    public function changeClass()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['id'] || !$data['grade'] || !$data['name'] || !$data['major_id']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        // try {
            $isPermission = $api->authority($tokenData['data']->number, 'update_class');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $class = new ClassModel;
            $result = $class->allowField(['name', 'grade', 'major_id'])
                ->save($data, ['id' => $data['id']]);

            if ($result) {
                $newData = $class->where('id', $data['id'])
                    ->find();
                $collegeName = json_decode(json_encode($newData->major->collegeNameByGetAll), true);
                $newData->hidden(['major', 'major_id']);
                $newData->appendRelationAttr('majorNameByGetAll', ['专业名']);
                $newData = array_merge(json_decode(json_encode($newData), true), $collegeName);

                return $api->return_msg(200, '修改成功！', $newData);
            } else {
                return $api->return_msg(401, '修改失败，数据未改动！');
            }
        // } catch (\Exception $th) {
        //     return $api->msg_500();
        // }
    }

    /**
     * 添加班级
     * @method [POST]
     * @method [POST]
     * @param [array] $data [班级详情]
     * @param [string] $data['grade'] [年级]
     * @param [string] $data['name'] [班级名]
     * @param [string] $data['major_id'] [专业ID]
     * @param [string] $token [Token]
     */
    public function addClass()
    {
        $api = new Api;

        $data = input('post.data/a');
        $token = input('post.token');

        if (!$data || !$token || !$data['name'] || !$data['grade'] || !$data['major_id']) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_class');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $class = new ClassModel;
            $classData = $class->where('name', $data['name'])
                ->find();
            if ($classData) {
                if ($classData['grade'] == $data['grade']) {
                    return $api->return_msg(401, '该班级已存在！请输入其它');
                }
            }

            $result = $class->allowField(true)
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
     * 批量添加专业
     * @method [POST]
     * @param [array] $classList [学院列表]
     * @param [string] $token [Token]
     */
    public function importClassList()
    {
        $api = new Api;

        $classList = input('post.classList/a');
        $token = input('post.token');

        if (!$classList || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_class');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $major = new MajorModel;
            $majorList = $major->field('id, name')
                ->select();
            $majorIsExist = true;
            foreach($classList as &$classItem) {
                foreach($majorList as $majorItem) {
                    if ($classItem['majorName'] == $majorItem['name']) {
                        $classItem['major_id'] = $majorItem['id'];
                        $majorIsExist = false;
                        break;
                    }
                };
                if ($majorIsExist) {
                    return $api->return_msg(401, $classItem['majorName'] . '：该专业不存在！');
                } else {
                    $majorIsExist = true;
                }
            }

            $listOfData = array_map(function($item) {
                return [
                    'grade' => $item['grade'], 'name' => $item['name']
                ];
            }, $classList);
            $class = new ClassModel;
            $listOfDataBase = $class->field('grade, name')
                ->select();
            $allList = array_merge($listOfData, json_decode(json_encode($listOfDataBase), true));
            if (count($allList) != count(array_unique($allList, SORT_REGULAR))) {
                return $api->return_msg(401, '导入失败！部分班级名已存在');
            };
            $result = $class->allowField(true)
                ->saveAll($classList);
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
     * 删除专业
     * @method [POST]
     * @param [string] $classId [学院ID]
     * @param [string] $token [Token]
     */
    public function deleteClass()
    {
        $api = new Api;

        $classId = input('post.classId/a');
        $token = input('post.token');

        if (!$classId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'delete_class');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $class = new ClassModel;

            $result = $class->destroy($classId);
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