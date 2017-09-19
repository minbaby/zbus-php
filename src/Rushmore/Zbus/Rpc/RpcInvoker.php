<?php

namespace Rushmore\Zbus\Rpc;

class RpcInvoker
{
    public $producer;
    public $token;

    public $topic;
    public $module;
    public $rpcSelector;
    public $rpcTimeout = 3;

    private $broker;

    public function __construct($broker, $topic)
    {
        $this->broker = $broker;
        $this->producer = new Producer($broker);
        $this->topic = $topic;
    }

    public function __call($method, $args)
    {
        if ($this->broker->isSync()) {
            return $this->callSync($method, $args);
        }

        $this->callAsync($method, $args);
    }

    private function callSync($method, $args)
    {
        $request = new Request($method, $args);
        $request->module = $this->module;

        $response = $this->invoke($request, $this->rpcTimeout, $this->rpcSelector);
        if ($response->error != null) {
            throw new RpcException((string)$response->error);
        }
        return $response->result;
    }


    private function callAsync($method, $args)
    {
        $params = array_slice($args, 0, count($args) - 1);
        $callback = $args[count($args) - 1];

        $request = new Request($method, $params);
        $request->module = $this->module;

        $this->invokeAsync($request, function ($response) use ($callback) {
            if ($response->error != null) {
                if (is_object($response) && is_a($response, Exception::class)) {
                    $error = $response->error;
                } else {
                    $error = new RpcException((string)$response->error);
                }

                call_user_func($callback, $error);
                return;
            }
            call_user_func($callback, $response->result);
        }, $this->rpcSelector);
    }

    public function invokeAsync($request, callable $callback, $selector = null)
    {
        if ($selector == null) {
            $selector = $this->rpcSelector;
        }

        $msg = new Message();
        $msg->topic = $this->topic;
        $msg->token = $this->token;
        $msg->ack = 0;

        $rpcBody = json_encode($request);
        $msg->setJsonBody($rpcBody);

        $this->producer->publishAsync($msg, function ($msgRes) use ($callback) {
            if ($msgRes->status != 200) {
                $res = new RpcException($msgRes->body);
                call_user_func($callback, $res);
                return;
            }

            $arr = json_decode($msgRes->body, true);
            $res = new Response();
            $res->error = @$arr['error'];
            $res->result = @$arr['result'];

            call_user_func($callback, $res);
        }, $selector);
    }

    public function invoke($request, $timeout = 3, $selector = null)
    {
        if ($selector == null) {
            $selector = $this->rpcSelector;
        }

        $msg = new Message();
        $msg->topic = $this->topic;
        $msg->token = $this->token;
        $msg->ack = 0;  //RPC no need ack

        $rpcBody = json_encode($request);
        $msg->setJsonBody($rpcBody);

        $msgRes = $this->producer->publish($msg, $timeout, $selector);
        if ($msgRes->status != 200) {
            throw new RpcException($msgRes->body);
        }

        $arr = json_decode($msgRes->body, true);
        $res = new Response();
        $res->error = @$arr['error'];
        $res->result = @$arr['result'];
        return $res;
    }
}
