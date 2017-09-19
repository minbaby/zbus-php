<?php

namespace Rushmore\Zbus\Mq;

use Exception;
use Rushmore\Zbus\Protocol;
use Rushmore\Zbus\ServerAddress;

class MqAdmin
{
    protected $broker;
    protected $adminSelector;
    protected $token;
    public function __construct($broker)
    {
        $this->broker = $broker;
        $this->adminSelector = function ($routeTable, $msg) {
            $serverTable = $routeTable->serverTable;
            $addressArray = [];
            foreach ($serverTable as $key => $serverInfo) {
                $serverAddress = new ServerAddress($serverInfo['serverAddress']);
                array_push($addressArray, $serverAddress);
            }
            return $addressArray;
        };
    }

    private function invokeCmdAsync($cmd, $topicCtrl, callable $callback, $selector = null)
    {
        if ($this->broker->isSync()) {
            throw new Exception("async should be enabled in broker");
        }
        $msg = buildMessage($topicCtrl, $cmd);
        if ($msg->token == null) {
            $msg->token = $this->token;
        }

        if ($selector == null) {
            $selector = $this->adminSelector;
        }
        $clientArray = $this->broker->select($selector, $msg);
        foreach ($clientArray as $client) {
            $client->invoke($msg, $callback);
        }
    }

    private function invokeObjectAsync($cmd, $topicCtrl, callable $callback, $selector = null)
    {
        if ($this->broker->isSync()) {
            throw new Exception("async should be enabled in broker");
        }

        $this->invokeCmdAsync($cmd, $topicCtrl, function ($msg) use ($callback) {
            $data = null;
            if ($msg->status != 200) {
                $data = new Exception($msg->body);
            } else {
                $data = json_decode($msg->body);
            }
            call_user_func($callback, $data);
        }, $selector);
    }

    private function invokeCmd($cmd, $topicCtrl, $timeout = 3, $selector = null)
    {
        if (!$this->broker->isSync()) {
            throw new Exception("sync should be enabled in broker");
        }
        $msg = buildMessage($topicCtrl, $cmd);
        if ($msg->token == null) {
            $msg->token = $this->token;
        }

        if ($selector == null) {
            $selector = $this->adminSelector;
        }
        $clientArray = $this->broker->select($selector, $msg);
        $resArray = [];
        foreach ($clientArray as $client) {
            $res = $client->invoke($msg, $timeout);
            array_push($resArray, $res);
        }
        return $resArray;
    }

    private function invokeObject($cmd, $topicCtrl, $timeout = 3, $selector = null)
    {
        if (!$this->broker->isSync()) {
            throw new Exception("sync should be enabled in broker");
        }

        $msgArray = $this->invokeCmd($cmd, $topicCtrl, $timeout, $selector);
        $resArray = [];
        foreach ($msgArray as $key => $msg) {
            $res = null;
            if ($msg->status != 200) {
                $res = new Exception($msg->body);
            } else {
                $res = json_decode($msg->body);
            }
            array_push($resArray, $res);
        }
        return $resArray;
    }

    public function query($topicCtrl, $timeout = 3, $selector = null)
    {
        return $this->invokeObject(Protocol::QUERY, $topicCtrl, $timeout, $selector);
    }

    public function queryAsync($topicCtrl, callable $callback, $selector = null)
    {
        $this->invokeObjectAsync(Protocol::QUERY, $topicCtrl, $callback, $selector);
    }

    public function declare_($topicCtrl, $timeout = 3, $selector = null)
    {
        return $this->invokeObject(Protocol::DECLARE_, $topicCtrl, $timeout, $selector);
    }

    public function declareAsync($topicCtrl, callable $callback, $selector = null)
    {
        $this->invokeObjectAsync(Protocol::DECLARE_, $topicCtrl, $callback, $selector);
    }

    public function remove($topicCtrl, $timeout = 3, $selector = null)
    {
        return $this->invokeCmd(Protocol::REMOVE, $topicCtrl, $timeout, $selector);
    }

    public function removeAsync($topicCtrl, callable $callback, $selector = null)
    {
        $this->invokeCmdAsync(Protocol::REMOVE, $topicCtrl, $callback, $selector);
    }
    public function empty_($topicCtrl, $timeout = 3, $selector = null)
    {
        return $this->invokeCmd(Protocol::EMPTY_, $topicCtrl, $timeout, $selector);
    }

    public function emptyAsync($topicCtrl, callable $callback, $selector = null)
    {
        $this->invokeCmdAsync(Protocol::EMPTY_, $topicCtrl, $callback, $selector);
    }
}
