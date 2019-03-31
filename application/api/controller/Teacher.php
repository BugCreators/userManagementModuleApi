<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\User as UserModel;
use app\api\model\VClass as ClassModel;
use app\api\model\College as CollegeModel;

class Teacher
{
    /**
     * 获取教师列表
     * @method [GET]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据] [0: 按学院名搜索] [2:按教师名搜索]
     * @param [string] $searchValue [搜索值]
     * @param [token] $token [Token]
     */
    public function getTeacherList()
    {
        $api = new Api;
        $pageSize = input('post.pageSize');
        $pageIndex = input('post.pageIndex');
        $searchBasis = input('post.searchBasis');
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_teacher');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $list = array();

            if ($searchBasis && $searchValue) {
                switch ($searchBasis) {
                    case '0':
                        $college = new CollegeModel;
                        $classList = array();

                        $collegeList = $college->where('name', 'like', $searchValue . '%')
                            ->select();
                        if ($collegeList) {
                            foreach ($college as $item) {
                                $classList = array_merge($classList, $item->class);
                            };
                            if ($classList) {
                                foreach($classList as $item) {
                                    $list = array_merge($list, $item->user()->where('role_id', 3));
                                };
                            };
                        };
                        $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
                        break;
                    case '2':
                        $user = new UserModel;

                        $list = $user->where('name', 'like', $searchValue . '%')
                            ->where('role_id', 3);
                            ->select();
                        $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
                        break;
                    default:
                        break;
                };
                $count = count($list);
            } else {
                $count = $user->where('role_id', 3)
                    ->count();
                $list = $user->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->where('role_id', 3)
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