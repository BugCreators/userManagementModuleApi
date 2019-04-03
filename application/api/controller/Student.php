<?php
namespace app\api\controller;

use app\api\controller\Api;
use app\api\model\User as UserModel;
use app\api\model\VClass as ClassModel;
use app\api\model\College as CollegeModel;

class Student
{
    /**
     * 获取学生列表
     * @method [GET]
     * @param [int] $pageSize []
     * @param [int] $pageIndex []
     * @param [string] $searchBasis [搜索依据] [0: 按学生名搜索] [1:按学院名搜索] [5:按班级名搜索]
     * @param [string] $searchValue [搜索值]
     * @param [token] $token [Token]
     */
    public function getStudentList()
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
            $isPermission = $api->authority($tokenData['data']->number, 'select_student');
            if ($isPermission == 0) {
                return $api->msg_405();
            }

            $user = new UserModel;
            $list = array();

            if ($searchValue) {
                switch ($searchBasis) {
                    case '0':
                        $user = new UserModel;
                        $list = $user->where('realname', 'like', '%' . $searchValue . '%')
                            ->where('role_id', 2)
                            ->select();
                        break;
                    case '1':
                        $college = new CollegeModel;
                        $collegeList = $college->where('name', 'like', '%' . $searchValue . '%')
                            ->select();
                        if ($collegeList) {
                            foreach ($collegeList as $collegeItem) {
                                $list = array_merge($list, $collegeItem->students);
                            };
                        }
                        break;
                    case '5':
                        $class = new ClassModel;
                        $classList = $class->where('name', 'like', '%' . $searchValue . '%')
                            ->select();
                        if ($classList) {
                            foreach ($classList as $classItem) {
                                $list = array_merge($list, $classItem->students);
                            };
                        };
                        break;
                    default:
                        return $api->msg_401();
                        break;
                };
                $count = count($list);
                $list = array_slice($list, $pageSize * ($pageIndex - 1), $pageSize);
            } else {
                $count = $user->where('role_id', 2)
                    ->count();
                $list = $user->field('id, realname, number, class_id, sex, phone, email, address, description')
                    ->limit($pageSize * ($pageIndex - 1), $pageSize)
                    ->where('role_id', 2)
                    ->order('id')
                    ->select();
            }
            foreach ($list as $item) {
                $item->className = $item->vclass->grade . $item->vclass->name;
                $item->collegeName = $item->vclass->major->college->name;
                $item->hidden(['vclass', 'class_id']);
            }
        } catch (\Exception $th) {
            return $api->msg_500();
        }

        return $api->msg_200([
            'count' => $count,
            'list' => $list
        ]);
    }

    public function getStudentDetail()
    {
        
    }
}
?>