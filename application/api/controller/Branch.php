<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\Branch as BranchModel;

class Branch
{
    /****************后台接口 BEGIN*******************/
    /**
     * 获取部门列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据]
     * @param [string] $searchValue [搜索值]
     * @param [string] $token [Token]
     */
    public function getBranchList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_branch');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $branch = new BranchModel;
            if ($searchValue) {
                if ($searchBasis == '0') {
                    $list = $branch->where('name', 'like', '%' . $searchValue . '%')
                    ->select();
                } elseif ($searchBasis == '4') {
                    if ($searchValue == '校级') {
                        $value = 0;
                    } elseif ($searchValue == '院级') {
                        $value = 1;
                    };
                    $list = $branch->where('level', $value)
                        ->select();
                } else {
                    return $api->msg_401();
                }
                $count = count($list);
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
            } else {
                $count = $branch->count();
                $list = $branch->limit($pageSize * ($pageIndex - 1), $pageSize)
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

    /**
     * 获取部门列表
     * @method [POST]
     * @param [string] $token [Token]
     */
    public function getAllBranchList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_branch');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $branch = new BranchModel;
            $list = $branch->field('name as 学院名, website as 官网链接, 
                operating_duty as 主要职能, description as 概述, level as 等级')
                ->select();
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200($list);
    }

    /**
     * 获取部门详情
     * @method [GET]
     * @param [string] $branchId [部门ID]
     * @param [string] $token [Token]
     */
    public function getBranchDetail() 
    {
        $api = new Api;
        $branchId = input('get.id');
        $token = input('get.token');
        
        if (!$branchId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'select_branch');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $branch = new BranchModel;
            $data = $branch->where('id', $branchId)
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
     * 编辑部门信息
     * @method [POST]
     * @param [array] $data [部门详情]
     * @param [string] $data['id'] [部门ID]
     * @param [string] $data['name'] [部门名]
     * @param [string] $data['website'] [官网链接]
     * @param [string] $data['operating_duty'] [工作职能]
     * @param [string] $data['description'] [概述]
     * @param [string] $token [Token]
     */
    public function changeBranch()
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
            $isPermission = $api->authority($tokenData['data']->number, 'update_branch');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $branch = new BranchModel;
            $branchName = $branch->where('id', $data['id'])
                ->value('name');
            if ($branchName != $data['name']) {
                $haveExisted = $branch->where('name', $data['name'])
                    ->find();
                if ($haveExisted) {
                    return $api->return_msg(401, '该部门已存在！');
                }
            }

            $result = $branch->allowField(['name', 'website', 'operating_duty', 'description'])
                ->save($data, ['id' => $data['id']]);

            if ($result) {
                return $api->return_msg(200, '修改成功！');
            } else {
                return $api->return_msg(401, '修改失败，数据未改动！');
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }
    }

    /**
     * 添加院系
     * @method [POST]
     * @param [array] $data [院系详情]
     * @param [string] $data['name'] [部门名]
     * @param [string] $data['website'] [官网链接]
     * @param [string] $data['operating_duty'] [工作职能]
     * @param [string] $data['description'] [概述]
     * @param [string] $data['level'] [等级]
     * @param [string] $token [Token]
     */
    public function addBranch()
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
            $isPermission = $api->authority($tokenData['data']->number, 'insert_branch');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $branch = new BranchModel;
            $haveExisted = $branch->where('name', $data['name'])
                ->find();
            if($haveExisted) {
                return $api->return_msg(401, '该部门已存在！请输入其它部门');
            }

            $result = $branch->allowField(true)
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
     * 批量添加院系
     * @method [POST]
     * @param [array] $branchList [院系列表]
     * @param [string] $token [Token]
     */
    public function importBranchList()
    {
        $api = new Api;

        $branchList = input('post.branchList/a');
        $token = input('post.token');

        if (!$branchList || !$token) {
            return $api->msg_401();
        }
        
        foreach ($branchList as $branchItem) {
            if (!$branchItem['name'] || !isset($branchItem['level'])) {
                return $api->msg_401();
            }
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'insert_branch');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $nameListOfData = array_map(function($item) {
                return $item['name'];
            }, $branchList);
            $branch = new BranchModel;
            $nameListOfDataBase = $branch->column('name');
            $allNameList = array_merge($nameListOfData, $nameListOfDataBase);
            if (count($allNameList) != count(array_unique($allNameList))) {
                return $api->return_msg(401, '导入失败！部分部门已存在');
            };

            $result = $branch->allowField(true)
                ->saveAll($branchList);
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
     * 删除院系
     * @method [POST]
     * @param [string] $branchsId [院系ID]
     * @param [string] $token [Token]
     */
    public function deleteBranch()
    {
        $api = new Api;

        $branchsId = input('post.branchsId/a');
        $token = input('post.token');

        if (!$branchsId || !$token) {
            return $api->msg_401();
        }

        $tokenData = $api->verification($token);
        if ($tokenData['code'] !== 200) {
            return $tokenData;
        };

        try {
            $isPermission = $api->authority($tokenData['data']->number, 'delete_branch');
            if (!$isPermission) {
                return $api->msg_405();
            }

            $branch = new BranchModel;

            $result = $branch->destroy($branchsId);
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