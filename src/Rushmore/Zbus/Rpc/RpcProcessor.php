<?php

namespace Rushmore\Zbus\Rpc;

use ErrorException;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use Rushmore\Zbus\Message;
use Rushmore\Zbus\Mq\BaseClient;
use Rushmore\Zbus\Mq\MqClient;
use Rushmore\Zbus\Request;
use Rushmore\Zbus\Response;

class RpcProcessor
{
    private $methods = [];

    public function addModule($service, $module = null)
    {
        if (is_string($service)) {
            $service = new $service();
        }
        $serviceClass = get_class($service);
        $class = new ReflectionClass($serviceClass);

        // http://php.net/manual/zh/reflectionclass.getmethods.php
        // Note: 请注意：其他位操作，例如 ~ 无法按预期运行。这个例子也就是说，无法获取所有的非静态方法
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        $methods = array_filter($methods, function (\ReflectionMethod $method) {
            return !$method->isStatic();
        });

        foreach ($methods as $method) {
            $key = $this->genKey($module, $method->name);
            $this->methods[$key] = [$method, $service];
        }
    }

    private function genKey($module, $method_name)
    {
        return "$module:$method_name";
    }

    private function process($request)
    {
        $key = $this->genKey($request->module, $request->method);
        $m = @$this->methods[$key];
        if ($m == null) {
            throw new ErrorException("Missing method $key");
        }
        $args = $request->params;
        if ($args === null) {
            $args = [];
        }
        $response = new Response();
        try {
            $response->result = $m[0]->invokeArgs($m[1], $args);
        } catch (Exception $e) {
            $response->error = $e;
        }
        return $response;
    }


    /**
     * @param Message $msg
     * @param BaseClient $client
     */
    public function messageHandler(Message $msg, BaseClient $client)
    {
        $msgRes = new Message();
        $msgRes->recver = $msg->sender;
        $msgRes->id = $msg->id;
        $msgRes->status = 200;

        $response = new Response();
        try {
            $json = json_decode($msg->body, true);
            $request = new Request();
            $request->method = @$json['method']; // TOOD 这么写其实不太好
            $request->params = @$json['params'];
            $request->module = @$json['module'];

            $response = $this->process($request);
        } catch (Exception $e) {
            $response->error = $e->getMessage();
        }
        $jsonRes = json_encode($response);
        $msgRes->setJsonBody($jsonRes);

        $client->route($msgRes);
    }
}
