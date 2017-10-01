<?php

namespace Rushmore\Zbus\Mq;

use Exception;
use Rushmore\Zbus\Logger;
use Rushmore\Zbus\Message;
use Rushmore\Zbus\Protocol;
use Rushmore\Zbus\ServerAddress;

class MqClient implements BaseClient
{
    public $sock;
    public $token;

    private $serverAddress;
    private $sslCertFile;

    private $recvBuf;
    private $resultTable = [];

    public function __construct($serverAddress, $sslCertFile = null)
    {
        $this->serverAddress = new ServerAddress($serverAddress);
        $this->sslCertFile = $sslCertFile;
    }

    /**
     * TODO timeout not used
     *
     * @param int $timeout
     */
    public function connect($timeout = 3)
    {
        $address = $this->serverAddress->address;
        $bb = explode(':', $address);
        $host = $bb[0];
        $port = 80;
        if (count($bb) > 1) {
            $port = intval($bb[1]);
        }

        Logger::debug("Trying connect to ($this->serverAddress)");
        $this->sock = @socket_create(AF_INET, SOCK_STREAM, 0);
        if (!@socket_connect($this->sock, $host, $port)) {
            $this->throw_socket_exception("Connection to ($address) failed");
        }
        Logger::debug("Connected to ($this->serverAddress)");
    }

    public function close()
    {
        if ($this->sock) {
            socket_close($this->sock);
            $this->sock = null;
        }
    }

    private function throw_socket_exception($msgPrefix = null)
    {
        $errorcode = socket_last_error($this->sock);
        $errormsg = socket_strerror($errorcode);
        $msg = "${msgPrefix}, $errorcode:$errormsg";
        Logger::error($msg);
        throw new Exception($msg);
    }


    /**
     * @param Message $msg
     * @param int $timeout
     * @return Message
     */
    public function invoke($msg, $timeout = 3)
    {
        $msgid = $this->send($msg, $timeout);
        return $this->recv($msgid, $timeout);
    }

    public function send($msg, $timeout = 3)
    {
        if ($this->sock == null) {
            $this->connect();
        }
        if ($msg->id == null) {
            $msg->id = uuid();
        }
        $buf = $msg->encode();
        Logger::debug($buf);
        $sendingBuf = $buf;
        $writeCount = 0;
        $totalCount = strlen($buf);
        while (true) {
            $n = socket_write($this->sock, $sendingBuf, strlen($sendingBuf));
            if ($n === false) {
                $this->throw_socket_exception("Socket write error");
            }
            $writeCount += $n;
            if ($writeCount >= $totalCount) {
                return;
            }
            if ($n > 0) {
                $sendingBuf = substr($sendingBuf, $n);
            }
        }
        return $msg->id;
    }

    public function recv($msgid = null, $timeout = 3)
    {
        if ($this->sock == null) {
            $this->connect();
        }

        $allBuf = '';
        while (true) {
            if ($msgid && array_key_exists($msgid, $this->resultTable)) {
                return $this->resultTable[$msgid];
            }

            $bufLen = 4096;
            $buf = socket_read($this->sock, $bufLen);
            //$buf = fread($this->sock, $buf_len);
            if ($buf === false || $buf == '') {
                $this->throw_socket_exception("Socket read error");
            }

            $allBuf .= $buf;

            $this->recvBuf .= $buf;
            $start = 0;
            while (true) {
                $res = Message::decode($this->recvBuf, $start);
                $msg = $res[0];
                $start = $res[1];
                if ($msg == null) {
                    if ($start != 0) {
                        $this->recvBuf = substr($this->recvBuf, $start);
                    }
                    break;
                }
                $this->recvBuf = substr($this->recvBuf, $start);

                if ($msgid != null) {
                    if ($msgid != $msg->id) {
                        $this->resultTable[$msg->id] = $msg;
                        continue;
                    }
                }
                Logger::debug($allBuf);
                return $msg;
            }
        }
    }

    private function invokeCmd($cmd, $topicCtrl, $timeout = 3)
    {
        $msg = buildMessage($topicCtrl, $cmd);
        $msg->token = $this->token;
        return $this->invoke($msg, $timeout);
    }

    private function invokeObject($cmd, $topicCtrl, $timeout = 3)
    {
        $res = $this->invokeCmd($cmd, $topicCtrl, $timeout);
        if ($res->status != 200) {
            throw new Exception($res->body);
        }

        return json_decode($res->body);
    }


    public function produce($msg, $timeout = 3)
    {
        return $this->invokeCmd(Protocol::PRODUCE, $msg, $timeout);
    }

    public function consume($topicCtrl, $timeout = 3)
    {
        $res = $this->invokeCmd(Protocol::CONSUME, $topicCtrl, $timeout);

        $res->id = $res->origin_id;
        $res->removeHeader(Protocol::ORIGIN_ID);
        if ($res->status == 200) {
            if ($res->origin_url != null) {
                $res->url = $res->origin_url;
                $res->status = null;
                $res->removeHeader(Protocol::ORIGIN_URL);
            }
        }
        return $res;
    }

    public function query($topicCtrl, $timeout = 3)
    {
        return $this->invokeObject(Protocol::QUERY, $topicCtrl, $timeout);
    }

    public function declare_($topicCtrl, $timeout = 3)
    {
        return $this->invokeObject(Protocol::DECLARE_, $topicCtrl, $timeout);
    }

    public function remove($topicCtrl, $timeout = 3)
    {
        return $this->invokeObject(Protocol::REMOVE, $topicCtrl, $timeout);
    }

    public function empty_($topicCtrl, $timeout = 3)
    {
        return $this->invokeObject(Protocol::EMPTY_, $topicCtrl, $timeout);
    }

    /**
     * @param Message $msg
     * @param int $timeout
     * @return string|void
     */
    public function route(Message $msg, $timeout = 3)
    {
        $msg->cmd = Protocol::ROUTE;
        if ($msg->status != null) {
            $msg->set_header(Protocol::ORIGIN_STATUS, $msg->status);
            $msg->status = null;
        }
        return $this->send($msg, $timeout);
    }
}
