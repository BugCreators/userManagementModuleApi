<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\Authority as AuthorityModel;
use app\api\model\Module as ModuleModel;

class Authority
{
    /**
     * 获取权限列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据] [0:按名字搜索] [3:按模块搜索]
     * @param [string] $searchValue [搜索值]
     * @param [string] [$token] [Token]
     */
    public function getAuthorityList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_authority');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $authority = new AuthorityModel;
            if ($searchValue) {
                if ($searchBasis == '0') {
                    $list = $authority->where('cn_name', 'like', '%' . $searchValue . '%')
                    ->select();
                    foreach($list as $item) {
                        $item->appendRelationAttr('module', ['moduleName']);
                    };
                } elseif ($searchBasis == '3') {
                    $module = new ModuleModel;
                    $list = array();
                    $moduleList = $module->where('cn_name', 'like', '%' . $searchValue . '%')
                        ->select();
                    foreach ($moduleList as $moduleItem) {
                        $authorityList = $moduleItem->authority;
                        foreach ($authorityList as $authorityItem) {
                            $authorityItem->moduleName = $moduleItem->cn_name;
                        }
                        $list = array_merge($list, $authorityList);
                    }
                }
                $count = count($list);
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
            } else {
                $count = $authority->count();
                $list = $authority->where('id', '>', $pageSize * ($pageIndex - 1))
                    ->limit($pageSize)
                    ->order('id')
                    ->select();
                foreach($list as $item) {
                    $item->appendRelationAttr('module', ['moduleName']);
                };
            }; 
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200([
            'count' => $count,
            'list' => $list
        ]);
    }
}
?>