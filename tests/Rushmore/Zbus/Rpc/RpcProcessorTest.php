<?php

namespace Test\Rushmore\Zbus\Rpc;

use Rushmore\Zbus\Message;
use Rushmore\Zbus\Mq\BaseClient;
use Rushmore\Zbus\Response;
use Rushmore\Zbus\Rpc\RpcProcessor;
use Test\Mock\Service\TwoMethodService;
use Test\TestCase;

class RpcProcessorTest extends TestCase
{
    public function testAddModule()
    {
        foreach ([null, 'testModule'] as $module) {
            foreach ([new TwoMethodService(), TwoMethodService::class] as $service) {
                $rpc = new RpcProcessor();
                $rpc->addModule($service, $module);
                $methods = $this->getProperty($rpc, 'methods');

                is_string($service) && $service = new $service();
                $this->assertCount(2, $methods);
                $this->assertEquals([
                    "{$module}:add" => [
                        $this->getMethod($service, 'add'),
                        $service
                    ],
                    "{$module}:retYes" => [
                        $this->getMethod($service, 'retYes'),
                        $service
                    ],
                ], $methods);
            }
        }
    }

    public function testMessageHandler()
    {
        $id = uuid();
        $user = 'sender';
        $body = json_encode([
            'method' => 'add',
            'params' => [1, 2],
            'module' => __METHOD__
        ]);
        $ret = new Message();
        $ret->id = $id;
        $ret->recver = $user;
        $ret->setJsonBody(json_encode(new Response(3)));
        $ret->status = 200;

        /** @var BaseClient $client */
        $client = \Mockery::mock(BaseClient::class)
            ->shouldReceive('route')
            ->andReturnUsing(function ($arg) use ($ret) {
                $this->assertEquals($ret, $arg);
            })
            ->once()
            ->getMock();

        $rpc = new RpcProcessor();
        $rpc->addModule(TwoMethodService::class, __METHOD__);

        $msg = new Message();
        $msg->id = $id;
        $msg->sender = $user;
        $msg->setJsonBody($body);

        $rpc->messageHandler($msg, $client);

        // test body Exception
        $ret->setJsonBody(json_encode(new Response(null, "json_decode() expects parameter 1 to be string, array given")));
        /** @var BaseClient $client */
        $client = \Mockery::mock(BaseClient::class)
            ->shouldReceive('route')
            ->andReturnUsing(function ($arg) use ($ret) {
                $this->assertEquals($ret, $arg);
            })
            ->once()
            ->getMock();

        $msg = new Message();
        $msg->id = $id;
        $msg->sender = $user;
        $msg->setJsonBody([]);

        $rpc->messageHandler($msg, $client);

        // test method not exists Exception
        $ret->setJsonBody(json_encode(new Response(null, "Missing method Test\\Rushmore\\Zbus\\Rpc\\RpcProcessorTest::testMessageHandler:add2")));
        /** @var BaseClient $client */
        $client = \Mockery::mock(BaseClient::class)
            ->shouldReceive('route')
            ->andReturnUsing(function ($arg) use ($ret) {
                $this->assertEquals($ret, $arg);
            })
            ->once()
            ->getMock();

        $msg = new Message();
        $msg->id = $id;
        $msg->sender = $user;
        $msg->setJsonBody(str_replace('add', 'add2', $body));

        $rpc->messageHandler($msg, $client);

        // test invoke Exception
        /** @var BaseClient $client */
        $client = \Mockery::mock(BaseClient::class)
            ->shouldReceive('route')
            ->andReturnUsing(function (Message $arg) {
                $this->assertRegExp("/Missing argument 1 for/", $arg->body);
            })
            ->once()
            ->getMock();

        $msg = new Message();
        $msg->id = $id;
        $msg->sender = $user;
        $msg->setJsonBody(str_replace('[1,2]', 'null', $body));

        $rpc->messageHandler($msg, $client);
    }
}
