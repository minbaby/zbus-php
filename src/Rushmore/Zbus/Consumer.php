<?php

namespace Rushmore\Zbus;

use Rushmore\Zbus\Mq\MqAdmin;
use Rushmore\Zbus\Mq\MqClient;
use Rushmore\Zbus\Mq\MqClientAsync;

class Consumer extends MqAdmin
{
    public $messageHandler;
    public $topic;
    public $consumeGroup;

    public $consumeSelector;
    public $connectionCount = 1;
    public $consumeClientTable = [];

    public function __construct($broker, $topic, $consumeGroup = null)
    {
        parent::__construct($broker);
        $this->topic = $topic;
        $this->consumeGroup = $consumeGroup;
        if ($this->consumeGroup == null) {
            $this->consumeGroup = new ConsumeGroup();
        }

        $this->consumeSelector = function ($routeTable, $msg) {
            $serverTable = $routeTable->serverTable;
            $addressArray = [];
            foreach ($serverTable as $key => $serverInfo) {
                $serverAddress = new ServerAddress($serverInfo['serverAddress']);
                array_push($addressArray, $serverAddress);
            }
            return $addressArray;
        };
    }

    public function start()
    {
        $c = $this;
        $this->broker->on('serverJoin', function ($client) use ($c) {
            $c->consumeToServer($client);
        });

        $this->broker->on('serverLeave', function ($serverAddress) use ($c) {
            $c->leaveServer($serverAddress);
        });
    }

    /**
     * @param MqClientAsync $client
     */
    private function consumeToServer($client)
    {
        $serverAddress = $client->serverAddress;
        $clientList = @$this->consumeClientTable[(string)$serverAddress];
        if ($clientList !== null) {
            return;
        }

        $msg = buildMessage($this->topic);
        $this->consumeGroup->topic = $msg->topic;
        $this->consumeGroup->toMessage($msg);
        $msg->token = $this->token;

        $clientList = [];
        for ($i = 0; $i < $this->connectionCount; $i++) {
            $forkedClient = $client->fork();
            array_push($clientList, $forkedClient);
            $this->consume($forkedClient, $msg);
        }
        $this->consumeClientTable[(string)$serverAddress] = $clientList;
    }

    private function leaveServer($serverAddress)
    {
        $clientList = @$this->consumeClientTable[(string)$serverAddress];
        if ($clientList === null) {
            return;
        }

        foreach ($clientList as $key => $client) {
            $client->close();
        }
        unset($this->consumeClientTable[(string)$serverAddress]);
    }

    /**
     * @param $client
     * @param $consumeCtrl
     */
    private function consume($client, $consumeCtrl)
    {
        $consumer = $this;
        $client->on('connected', function () use ($consumer, $client, $consumeCtrl) {
            $client->declare_($consumeCtrl, function ($res) use ($consumer, $client, $consumeCtrl) {
                if (is_a($res, 'Exception')) {
                    Logger::error('Declare error: ' . $res->getMessage());
                    return;
                }
                $client->consume($consumeCtrl, function ($res) use ($consumer, $client, $consumeCtrl) {
                    $consumer->consumeCallback($client, $consumeCtrl, $res);
                });
            });
        });

        $client->connect();
    }

    private function consumeCallback($client, $consumeCtrl, $res)
    {
        $consumer = $this;
        if ($res->status == 404) {
            $client->declare_($consumeCtrl, function ($res) use ($consumer, $client, $consumeCtrl) {
                if (is_a($res, 'Exception')) {
                    Logger::error('Declare error: ' . $res->getMessage());
                    return;
                }
                $client->consume($consumeCtrl, function ($res) use ($consumer, $client,$consumeCtrl) {
                    $consumer->consumeCallback($client, $consumeCtrl, $res);
                });
            });
            return;
        }

        $originUrl = $res->origin_url;
        $id = $res->origin_id;
        $res->removeHeader('origin_url');
        $res->removeHeader('origin_id');
        if ($originUrl !== null) {
            $res->url = $originUrl;
        }
        $res->id = $id;

        if ($this->messageHandler !== null) {
            try {
                call_user_func($this->messageHandler, $res, $client);
            } catch (Exception $e) {
                Logger::warn($e->getMessage());
            } finally {
                $client->consume($consumeCtrl, function ($res) use ($consumer,$client, $consumeCtrl) {
                    $consumer->consumeCallback($client, $consumeCtrl, $res);
                });
            }
        }
    }
}
