<?php

namespace Rushmore\Zbus\Mq;

use Exception;
use Rushmore\Zbus\EventEmitter;
use Rushmore\Zbus\Logger;
use Rushmore\Zbus\Message;
use Rushmore\Zbus\Protocol;
use Rushmore\Zbus\ServerAddress;
use Rushmore\Zbus\Stream;

class MqClientAsync
{
    use EventEmitter;

    public $token;
    public $serverAddress;

    private $stream;
    private $loop;
    private $sslCertFile;

    private $recvBuffer;
    private $callbackTable = [];

    private $heartbeator;
    private $heartbeatInterval = 60; //60seconds
    private $connectTimeout = 3;
    private $autoReconnect = true;
    private $connectTimer;

    public function __construct($address, $loop, $sslCertFile = null, $heartbeatInterval = 60)
    {
        $this->serverAddress = new ServerAddress($address);
        $this->loop = $loop;
        $this->sslCertFile = $sslCertFile;

        $this->heartbeatInterval = $heartbeatInterval;
        $that = $this;

        $this->heartbeator = $loop->addTimer($this->heartbeatInterval, function () use ($that) {
            $that->heartbeat();
        }, true);
    }

    public function fork()
    {
        return new MqclientAsync($this->serverAddress, $this->loop, $this->sslCertFile, $this->heartbeatInterval);
    }

    public function connect(callable $connected = null)
    {
        $address = $this->serverAddress->address;
        Logger::debug('Trying connect to ' . $address);
        $context = [];
        $errno = null;
        $errstr = null;
        $socket = @stream_socket_client(
            'tcp://'.$address,
            $errno,
            $errstr,
            0,
            STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT,
            stream_context_create($context)
        );

        if ($socket === false) {
            $this->emit('error', [new Exception("Connection to ($address) failed, $errstr")]);
            return;
        }

        $client = $this;
        $this->stream = null;

        $this->connectTimer = $this->loop->addTimer($this->connectTimeout, function () use ($client, $socket, $connected, $address) {
            if (is_resource($socket) && stream_socket_get_name($socket, true) === false) {
                $client->loop->removeWriteStream($socket);
                fclose($socket);
            }

            if ($client->stream == null) {
                Logger::warn('Connection (' . $address . ') timeout');
                if ($client->autoReconnect) {
                    $client->connect($connected);
                }
            }
        });

        $this->loop->addWriteStream($socket, function ($socket) use ($client, $connected, $address) {
            $client->loop->removeWriteStream($socket);

            if (stream_socket_get_name($socket, true) === false) {
                fclose($socket);
                return;
            }
            Logger::debug('Connected to (' . $address . ')');
            $client->createStream($socket, $connected);
        });
    }

    private function createStream($socket, $connected)
    {
        $client = $this;
        $client->stream = new Stream($socket, $client->loop);
        if ($connected) {
            $connected();
        }
        $client->emit('connected');

        $client->stream->on('data', function ($data) use ($client) {
            $client->recvBuffer .= $data;
            $start = 0;
            while (true) {
                $res = Message::decode($client->recvBuffer, $start);
                $msg = $res[0];
                $start = $res[1];
                if ($msg === null) {
                    if ($start != 0) {
                        $client->recvBuffer = substr($client->recvBuffer, $start);
                    }
                    break;
                }

                $callback = @$client->callbackTable[$msg->id];
                if ($callback !== null) {
                    try {
                        unset($client->callbackTable[$msg->id]);
                        $callback($msg);
                    } catch (Exception $e) {
                        Logger::error($e->getMessage());
                    }
                } else {
                    $client->emit('message', [$msg]);
                }
            }
        });

        $client->stream->on('error', function ($data) use ($client) {
            $client->emit('error', [$data]);
        });

        $client->stream->on('close', function ($data) use ($client) {
            $client->emit('close', [$data]);
        });

        $client->stream->on('drain', function () use ($client) {
            $client->emit('drain', []);
        });
    }

    public function close()
    {
        if ($this->stream !== null) {
            $this->stream->close();
        }
        $this->loop->cancelTimer($this->connectTimer);
        $this->loop->cancelTimer($this->heartbeator);
    }

    protected function heartbeat()
    {
        if ($this->stream == null || !$this->stream->isActive()) {
            return;
        }
        $msg = new Message();
        $msg->cmd = Protocol::HEARTBEAT;
        $this->invoke($msg);
    }

    public function invoke($msg, callable $callback = null)
    {
        if ($msg->id == null) {
            $msg->id = uuid();
        }
        if ($callback) {
            $this->callbackTable[$msg->id] = $callback;
        }

        $buf = $msg->encode();
        $this->stream->write($buf);
    }


    private function invokeCmd($cmd, $topicCtrl, callable $callback = null)
    {
        $msg = buildMessage($topicCtrl, $cmd);
        $msg->token = $this->token;
        return $this->invoke($msg, $callback);
    }

    private function invokeObject($cmd, $topicCtrl, callable $callback = null)
    {
        $this->invokeCmd($cmd, $topicCtrl, function ($res) use ($callback) {
            if ($res->status != 200) {
                $error = new Exception($res->body);
                $callback(['error' => $error]);
                return;
            }
            try {
                $obj = json_decode($res->body);
                $callback($obj);
            } catch (Exception $e) {
                $callback(['error' => $e]);
            }
        });
    }

    public function query($topicCtrl, callable $callback = null)
    {
        return $this->invokeObject(Protocol::QUERY, $topicCtrl, $callback);
    }

    public function declare_($topicCtrl, callable $callback = null)
    {
        return $this->invokeObject(Protocol::DECLARE_, $topicCtrl, $callback);
    }

    public function remove($topicCtrl, $callback = null)
    {
        return $this->invokeCmd(Protocol::REMOVE, $topicCtrl, $callback);
    }

    public function empty_($topicCtrl, $callback = null)
    {
        return $this->invokeCmd(Protocol::EMPTY_, $topicCtrl, $callback);
    }

    public function produce($msg, callable $callback = null)
    {
        if ($callback == null) {
            $msg->ack = false;
        }
        return $this->invokeCmd(Protocol::PRODUCE, $msg, $callback);
    }

    public function consume($topicCtrl, callable $callback)
    {
        $this->invokeCmd(Protocol::CONSUME, $topicCtrl, function ($res) use ($callback) {
            $res->id = $res->origin_id;
            $res->removeHeader(Protocol::ORIGIN_ID);
            if ($res->status == 200) {
                if ($res->origin_url != null) {
                    $res->url = $res->origin_url;
                    $res->removeHeader(Protocol::ORIGIN_URL);
                }
            }
            if ($callback) {
                $callback($res);
            }
        });
    }

    public function route($msg)
    {
        $msg->cmd = Protocol::ROUTE;
        if ($msg->status != null) {
            $msg->setHeader(Protocol::ORIGIN_STATUS, $msg->status);
            $msg->status = null;
        }
        return $this->invoke($msg);
    }
}
