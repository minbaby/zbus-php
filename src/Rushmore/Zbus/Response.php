<?php
/**
 * Created by PhpStorm.
 * User: zhangshaomin
 * Date: 2017/9/19
 * Time: 15:21
 */

namespace Rushmore\Zbus;


class Response{
    public $result;
    public $error;

    public function __construct($result=null, $error=null){
        $this->result = $result;
        $this->error = $error;
    }

    public function __toString(){
        return json_encode($this);
    }
}