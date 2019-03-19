<?php
namespace app\bean;

class Bean
{
  public $data = null;
  public $success = false;
  public $message = '';
  public function setData($data, $success, $message = '') {
    $this->data = $data;
    $this->success = $success;
    $this->message = $message;
  }
}
?>