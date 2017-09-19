<?php

namespace Test;

use Rushmore\Zbus\ConsumeGroup;
use Rushmore\Zbus\Message;
use stdClass;

class HelperTest extends TestCase
{
    public function testUuid()
    {
        $uuid = uuid();
        self::assertRegExp("/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/", $uuid, 'format error');
    }

    /**
     * @expectedException \Exception
     */
    public function testBuildMessage()
    {
        // $topicCtrl == string
        $topicCtrl = 'string';
        $msg = buildMessage($topicCtrl);
        $this->assertEquals($msg->topic, $topicCtrl);

        // $topicCtrl == string and $cmd = 'cmd'
        $cmd = 'cmd';
        $msg = buildMessage($topicCtrl, $cmd);
        $this->assertInstanceOf(Message::class, $msg);
        $this->assertEquals($msg->topic, $topicCtrl);
        $this->assertEquals($msg->cmd, $cmd);

        $msgMock = new Message();
        $msgMock->topic = $topicCtrl;
        $msg = buildMessage($msgMock);
        $this->assertInstanceOf(Message::class, $msg);
        $this->assertSame($msg, $msgMock);
        $this->assertEquals($msg->topic, $topicCtrl);

        $msgMock = new ConsumeGroup();
        $msgMock->topic = $topicCtrl;
        $msg = buildMessage($msgMock);
        $this->assertInstanceOf(Message::class, $msg);
        $this->assertEquals($msg->topic, $topicCtrl);

        $topicCtrlArr = [
            'topic' => $topicCtrl,
            'some'  => $cmd
        ];

        $msg = buildMessage($topicCtrlArr);
        $this->assertInstanceOf(Message::class, $msg);
        $this->assertEquals($msg->topic, $topicCtrl);
        $this->assertEquals($msg->some, $cmd);

        // to Exception
        $topicCtrl = 1;
        buildMessage($topicCtrl);

        // to Exception
        $topicCtrl = new stdClass();
        buildMessage($topicCtrl);
    }
}