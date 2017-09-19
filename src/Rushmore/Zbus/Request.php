<?php
namespace Rushmore\Zbus;

class Request{
    public $method;
    public $params;
    public $module;

    public function __construct($method=null, $params=null, $module=null){
        $this->method = $method;
        $this->params = $params;
        $this->module = $module;
    }
}