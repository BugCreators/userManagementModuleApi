<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\Authority as AuthorityModel;
use app\api\model\User as UserModel;

class Authority
{
    /**
     * 获取权限列表
     * @method [POST]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $AuthorityName [权限名]
     * @param [string] [$token] [Token]
     */
    public function getAuthorityList()
    {
        $api = new Api;
        $pageSize = input('post.pageSize');
        $pageIndex = input('post.pageIndex');
        $authorityName = input('post.searchValue.name');
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
            if ($authorityName) {
                $list = $authority->where('name', 'like', $authorityName . '%')
                    ->select();
                $count = count($list);
            } else {
                $count = $authority->count();
                $list = $authority->where('id', '>', $pageSize * ($pageIndex - 1))
                    ->limit($pageSize)
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
}
?>