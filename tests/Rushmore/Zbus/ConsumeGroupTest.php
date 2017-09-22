<?php

namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\ConsumeGroup;
use Rushmore\Zbus\Message;
use Rushmore\Zbus\Protocol;
use Test\TestCase;

class ConsumeGroupTest extends TestCase
{

    protected function setUp()
    {
    }

    protected function tearDown()
    {
        \Mockery::close();
    }

    public function testConstruct()
    {
        $consumeGroup = new ConsumeGroup();
        $this->assertNull($consumeGroup->groupName);
        $this->assertNull($consumeGroup->filter);

        $consumeGroup = new ConsumeGroup('groupName', 'filter');
        $this->assertEquals('groupName', $consumeGroup->groupName);
        $this->assertEquals('filter', $consumeGroup->filter);
    }

    public function testFromMessage()
    {
        $message = new Message();
        $message->setHeader(Protocol::TOPIC, "TOPIC");
        $message->setHeader(Protocol::CONSUME_GROUP, "CONSUME_GROUP");
        $message->setHeader(Protocol::GROUP_FILTER, "GROUP_FILTER");
        $message->setHeader(Protocol::GROUP_START_COPY, "GROUP_START_COPY");
        $message->setHeader(Protocol::GROUP_START_OFFSET, "GROUP_START_OFFSET");
        $message->setHeader(Protocol::GROUP_START_MSGID, "GROUP_START_MSGID");
        $message->setHeader(Protocol::GROUP_START_TIME, "GROUP_START_TIME");

        $consumeGroup = new ConsumeGroup();
        $consumeGroup->fromMessage($message);

        $this->assertEquals("TOPIC", $consumeGroup->topic);
        $this->assertEquals("CONSUME_GROUP", $consumeGroup->groupName);
        $this->assertEquals("GROUP_FILTER", $consumeGroup->filter);
        $this->assertEquals("GROUP_START_COPY", $consumeGroup->startCopy);
        $this->assertEquals("GROUP_START_OFFSET", $consumeGroup->startOffset);
        $this->assertEquals("GROUP_START_MSGID", $consumeGroup->startMsgId);
        $this->assertEquals("GROUP_START_TIME", $consumeGroup->startTime);

        return [$consumeGroup, $message];
    }

    /**
     * @depends testFromMessage
     * @param array $arr
     */
    public function testToMessage(array $arr)
    {
        /** @var ConsumeGroup $consumeGroup */
        /** @var Message $message */
        list($consumeGroup, $message) = $arr;
        $msg = new Message();
        $consumeGroup->toMessage($msg);
        $this->assertEquals($message, $msg);
    }
}
