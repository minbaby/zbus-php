<?php

namespace Rushmore\Zbus;

use Rushmore\Zbus\Mq\MqClient;
use Rushmore\Zbus\Mq\MqClientAsync;

class Broker
{
    use EventEmitter;

    public $routeTable;

    private $syncEnabled = false;
    private $clientTable = []; //store MqClientAsync or MqClient

    private $sslCertFileTable = [];
    private $loop;
    private $autoReconnectTimeout = 3;

    private $trackerSubscribers = [];
    private $readyTriggered = false; //any tracker tiggered is considered broker ready triggered.

    public function __construct(EventLoop $loop, $trackerAddressList = null, $syncEnabled = false)
    {
        $this->loop = $loop;
        $this->routeTable = new BrokerRouteTable();
        $this->syncEnabled = $syncEnabled;

        if ($trackerAddressList) {
            $bb = explode(';', $trackerAddressList);
            foreach ($bb as $trackerAddress) {
                $this->addTracker($trackerAddress);
            }
        }
    }

    public function addTracker($trackerAddress, $sslCertFile = null)
    {
        $client = new MqClientAsync($trackerAddress, $this->loop, $sslCertFile);
        $trackerSubscriber = new TrackerSubscriber($client);
        $this->trackerSubscribers[$trackerAddress] = $trackerSubscriber;
        $broker = $this;
        $remoteTrackerAddress = $trackerAddress;
        $client->on('message', function ($msg) use ($broker, $trackerSubscriber, &$remoteTrackerAddress, $sslCertFile) {
            if ($msg->status != 200) {
                Logger::error('track_sub status warning');
                return;
            }

            $trackerInfo = json_decode($msg->body, true);

            $remoteTrackerAddress = new ServerAddress($trackerInfo['serverAddress']);
            if ($sslCertFile) {
                $broker->sslCertFileTable[(string)$remoteTrackerAddress] = $sslCertFile;
            }
            if (@$this->trackerSubscribers[(string)$remoteTrackerAddress] === null) {
                $this->trackerSubscribers[(string)$remoteTrackerAddress] = $trackerSubscriber;
            }
            if (!$trackerSubscriber->readyTriggered) {
                $trackerSubscriber->readyCount = count($trackerInfo['serverTable']);
            }

            $toRemove = $this->routeTable->updateTracker($trackerInfo);
            $serverTable = $broker->routeTable->serverTable;
            foreach ($serverTable as $key => $serverInfo) {
                $broker->addServer($serverInfo, $trackerSubscriber);
            }
            foreach ($toRemove as $key => $serverAddress) {
                $broker->removeServer($serverAddress);
            }

            $broker->emit('trackerUpdate', [$broker]);
        });

        $client->on('close', function () use ($client, $broker, &$remoteTrackerAddress) {
            $toRemove = $broker->routeTable->removeTracker($remoteTrackerAddress);
            foreach ($toRemove as $key => $serverAddress) {
                $broker->removeServer($serverAddress);
            }
        });

        $client->on('connected', function () use ($client) {
            $msg = new Message();
            $msg->cmd = Protocol::TRACK_SUB;
            $client->invoke($msg);
        });

        $client->on('error', function ($error) use ($client, $broker) {
            Logger::error($error->getMessage());
            $broker->loop->addTimer($broker->autoReconnectTimeout, function () use ($client) {
                $client->connect();
            });
        });

        $client->connect();
    }

    private function addServer($serverInfo, $trackerSubscriber)
    {
        $serverAddress = new ServerAddress($serverInfo['serverAddress']);
        $client = @$this->clientTable[(string)$serverAddress];
        if ($client !== null) {
            return; //client already exists
        }
        $sslCertFile = @$this->sslCertFileTable[(string)$serverAddress];
        $client = $this->createClient($serverAddress, $this->loop, $sslCertFile);
        $this->clientTable[(string)$serverAddress] = $client;
        $broker = $this;

        if ($broker->syncEnabled) { //for sync mode
            $broker->emit('serverJoin', [$client]);
            if (!$trackerSubscriber->readyTriggered) {
                $trackerSubscriber->readyCount--;
                if ($trackerSubscriber->readyCount <= 0) {
                    if (!$broker->readyTriggered) {
                        $broker->emit('ready');
                        $broker->readyTriggered = true;
                    }
                    $trackerSubscriber->readyTriggered = true;
                }
            }
            return;
        }

        //async MqClient
        $client->on('connected', function () use ($broker, $client, $serverAddress,  $trackerSubscriber) {
            $broker->emit('serverJoin', [$client]);
            if (!$trackerSubscriber->readyTriggered) {
                $trackerSubscriber->readyCount--;
                if ($trackerSubscriber->readyCount <= 0) {
                    if (!$broker->readyTriggered) {
                        $broker->emit('ready');
                        $broker->readyTriggered = true;
                    }
                    $trackerSubscriber->readyTriggered = true;
                }
            }
        });
        $client->connect();
    }


    private function removeServer($serverAddress)
    {
        $client = @$this->clientTable[(string)$serverAddress];
        if ($client === null) {
            return;
        }
        $this->emit('serverLeave', [$serverAddress]);
        unset($this->clientTable[(string)$serverAddress]);
        $client->close();
    }

    private function createClient($serverAddress, $sslCertFile = null)
    {
        if ($this->syncEnabled) {
            return new MqClient($serverAddress, $sslCertFile);
        }
        return new MqClientAsync($serverAddress, $this->loop, $sslCertFile);
    }

    /**
     * @param $selector
     * @param $msg
     * @return Rpc\RpcInvoker[]
     */
    public function select($selector, $msg)
    {
        $addressList = $selector($this->routeTable, $msg);
        if (!is_array($addressList)) {
            $addressList = [$addressList];
        }

        $clientSelected = [];
        foreach ($addressList as $address) {
            $client = @$this->clientTable[(string)$address];
            if ($client == null) {
                Logger::warn("Missing client for " . $address);
                continue;
            }
            array_push($clientSelected, $client);
        }
        return $clientSelected;
    }

    public function close()
    {
        foreach ($this->trackerSubscribers as $key => $sub) {
            $sub->client->close();
        }
        $this->trackerSubscribers = [];

        foreach ($this->clientTable as $key => $client) {
            $client->close();
        }
        $this->clientTable = [];
    }

    public function isSync()
    {
        return $this->syncEnabled;
    }
}
