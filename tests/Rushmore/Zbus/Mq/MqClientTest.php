<?php

namespace Test\Rushmore\Zbus\Mq;

use Rushmore\Zbus\Message;
use Rushmore\Zbus\Mq\MqClient;
use Test\TestCase;

class MqClientTest extends TestCase
{
    /**
     * @var MqClient
     */
    private $mqClient;

    protected function setUp()
    {
        $this->mqClient = new MqClient($this->zbusServer);
    }

    /**
     * TODO test ssl
     */
    public function testConstruct()
    {
        $this->assertEquals($this->zbusServer, $this->getProperty($this->mqClient, 'serverAddress'));
        $this->assertNull($this->getProperty($this->mqClient, 'sslCertFile'));
    }


    /**
     * @expectedException \Exception
     */
    public function testConnect()
    {
        $this->mqClient->connect();
        $this->assertNotNull($this->mqClient->sock);
        $this->assertInstanceOf(MqClient::class, $this->mqClient);

        $mq = new MqClient('127.0.0.1:1212');
        $mq->connect();
    }

    public function testClose()
    {
        $this->mqClient->connect();
        $this->assertNotNull($this->mqClient->sock);
        $this->assertInstanceOf(MqClient::class, $this->mqClient);

        $this->mqClient->close();
        $this->assertNull($this->mqClient->sock);
    }

    public function testInvoke()
    {
        $msg = new Message();
        $ret = $this->mqClient->invoke($msg, 1);

        $this->assertEquals(200, $ret->status);
        $this->assertEquals($msg->id, $ret->id);
    }
}
