<?php

namespace Rushmore\Zbus;

use Exception;
use Rushmore\Zbus\Mq\MqAdmin;

class Producer extends MqAdmin
{
    protected $produceSelector;

    public function __construct($broker)
    {
        parent::__construct($broker);

        $this->produceSelector = function ($routeTable, $msg) {
            if ($msg->topic == null) {
                throw new Exception("Missing topic");
            }
            if (count($routeTable->serverTable) < 1) {
                return [];
            }
            $topicTable = $routeTable->topicTable;
            $serverList = @$topicTable[$msg->topic];
            if ($serverList == null || count($serverList) < 1) {
                return [];
            }
            $target = null;
            foreach ($serverList as $topicInfo) {
                if ($target == null) {
                    $target = $topicInfo;
                    continue;
                }
                if ($target['consumerCount'] < $topicInfo['consumerCount']) {
                    $target = $topicInfo;
                }
            }
            $res = [];
            array_push($res, new ServerAddress($target['serverAddress']));
            return $res;
        };
    }

    public function publishAsync($msg, callable $callback, $selector = null)
    {
        if ($selector == null) {
            $selector = $this->produceSelector;
        }

        $msg->cmd = Protocol::PRODUCE;
        if ($msg->token == null) {
            $msg->token = $this->token;
        }

        $clientArray = $this->broker->select($selector, $msg);
        if (count($clientArray) < 1) {
            throw new Exception("Missing MqServer for $msg");
        }

        foreach ($clientArray as $key => $client) {
            $client->invoke($msg, $callback);
        }
    }

    public function publish($msg, $timeout = 3, $selector = null)
    {
        if ($selector == null) {
            $selector = $this->produceSelector;
        }

        $msg->cmd = Protocol::PRODUCE;
        if ($msg->token == null) {
            $msg->token = $this->token;
        }

        $clientArray = $this->broker->select($selector, $msg);
        if (count($clientArray) < 1) {
            throw new Exception("Missing MqServer for $msg");
        }

        $resArray = [];
        foreach ($clientArray as $key => $client) {
            $res = $client->invoke($msg, $timeout);
            array_push($resArray, $res);
        }
        if (count($resArray) == 1) {
            return $resArray[0];
        }
        return $resArray;
    }
}
