<?php
namespace app\api\controller;

use app\api\model\College as CollegeModel;
use app\bean\Bean as DataBean;

class College
{
    public function sysSetting()
    {
      $dataBean = new DataBean();
      $dataBean->setData('111', true);
      return $dataBean;
    }

    public function collegeList()
    {
      $dataBean = new DataBean();
      try{
        $college = new CollegeModel();
        $list = $college->order('id')
          ->select();
        $dataBean->data = $list;
        $dataBean->success = true;
      }catch(\Exception $e){
        $dataBean->setData(null, false, '系统错误，请联系管理员！');
      };
      return $dataBean;
    }

    public function collegeDetail()
    {
      $dataBean = new DataBean();
      $id = input('id');
      if(!$id){
        $dataBean = new DataBean(null, false, '学院id为空！');
        return $dataBean;
      }
      try{
        $college = new CollegeModel();
        $data = $college->where('id', $id)
          ->find();
        if($data){
          $dataBean->data = $data;
          $dataBean->success = true;
        }else{
          $dataBean->message = '找不到该学院！';
        }
      }catch(\Exception $e){
        $dataBean->setData(null, false, '系统错误，请联系管理员！');
      }
      return $dataBean;
    }
}
?>