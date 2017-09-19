<?php
/**
 * Created by PhpStorm.
 * User: zhangshaomin
 * Date: 2017/9/19
 * Time: 15:26
 */

namespace Rushmore\Zbus;

class RpcProcessor {
    private $methods = array();

    public function addModule($service, $module=null){
        if(is_string($service)){
            $service = new $service();
        }
        $serviceClass = get_class($service);
        $class = new ReflectionClass($serviceClass);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC & ~ReflectionMethod::IS_STATIC);
        foreach($methods as $method){
            $key = $this->genKey($module, $method->name);
            $this->methods[$key] = array($method, $service);
        }
    }

    private function genKey($module, $method_name){
        return "$module:$method_name";
    }

    private function process($request){
        $key = $this->genKey($request->module, $request->method);
        $m = @$this->methods[$key];
        if($m == null){
            throw new ErrorException("Missing method $key");
        }
        $args = $request->params;
        if($args === null){
            $args = array();
        }
        $response = new Response();
        try{
            $response->result = $m[0]->invokeArgs($m[1], $args);
        } catch (Exception $e){
            $response->error = $e;
        }
        return $response;
    }


    public function messageHandler($msg, $client){
        $msgRes = new Message();
        $msgRes->recver = $msg->sender;
        $msgRes->id = $msg->id;
        $msgRes->status = 200;

        $response = new Response();
        try{
            $json = json_decode($msg->body, true);
            $request = new Request();
            $request->method = @$json['method'];
            $request->params = @$json['params'];
            $request->module = @$json['module'];

            $response = $this->process($request);

        } catch (Exception $e){
            $response->error = $e->getMessage();
        }
        $jsonRes = json_encode($response);
        $msgRes->setJsonBody($jsonRes);

        $client->route($msgRes);
    }
}