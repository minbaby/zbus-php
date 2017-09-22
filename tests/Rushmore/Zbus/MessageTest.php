<?php

namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\Message;
use Test\TestCase;

class MessageTest extends TestCase
{

    protected $map = HTTP_STATUS_TABLE;

    /**
     * @var Message
     */
    protected $message;

    protected function setUp()
    {
        $this->message = new Message();
    }


    public function testSetGetRemoveHeader()
    {
        $this->message->setHeader("key", "value");
        $value = $this->message->getHeader("key");
        $this->assertEquals('value', $value);

        $this->message->removeHeader('key');
        $value = $this->message->getHeader("key");
        $this->assertNull($value);


        $this->message->setHeader("key", null);
        $value = $this->message->getHeader('key');
        $this->assertNull($value);

        $value = $this->message->removeHeader('key');
        $this->assertNull($value);
    }

    public function testSetJsonBody()
    {
        $this->message->setJsonBody("value");
        $this->assertEquals("value", $this->message->body);
        $this->assertEquals($this->message->getHeader('content-type'), "application/json");
    }

    public function testMagicMethod()
    {
        $this->message->key = "value";
        $this->assertEquals("value", $this->message->key);

        $this->message->key2 = null;
        $this->assertNull($this->message->key2);

        $this->assertNull($this->message->key3);
    }

    public function testEncode()
    {
        $message = new Message();
        $encode = $message->encode();
        $expected = "GET / HTTP/1.1\r\ncontent-length: 0\r\n\r\n";
        $this->assertEquals($expected, $encode);

        $message = new Message();
        $message->status = 200;
        $encode = $message->encode();
        $expected = "HTTP/1.1 {$message->status} {$this->map[$message->status]}\r\ncontent-length: 0\r\n\r\n";
        $this->assertEquals($expected, $encode);

        $message = new Message();
        $message->setHeader("a", "b");
        $encode = $message->encode();
        $expected = "GET / HTTP/1.1\r\na: b\r\ncontent-length: 0\r\n\r\n";
        $this->assertEquals($expected, $encode);

        $message = new Message();
        $message->status = 211;
        $encode = $message->encode();
        $expected = "HTTP/1.1 {$message->status} unknown status\r\ncontent-length: 0\r\n\r\n";
        $this->assertEquals($expected, $encode);

        $message = new Message();
        $message->status = 200;
        $message->setHeader("content-length", 100);
        $encode = $message->encode();
        $expected = "HTTP/1.1 {$message->status} {$this->map[$message->status]}\r\ncontent-length: 0\r\n\r\n";
        $this->assertEquals($expected, $encode);

        $message = new Message();
        $message->status = 200;
        $message->setHeader("A", 'B');
        $encode = $message->encode();
        $expected = "HTTP/1.1 {$message->status} {$this->map[$message->status]}\r\nA: B\r\ncontent-length: 0\r\n\r\n";
        $this->assertEquals($expected, $encode);

        $message = new Message();
        $message->status = 200;
        $message->setHeader("A", 'B');
        $message->body = "body";
        $encode = $message->encode();
        $len = strlen($message->body);
        $expected = "HTTP/1.1 {$message->status} {$this->map[$message->status]}\r\nA: B\r\ncontent-length: {$len}\r\n\r\n{$message->body}";
        $this->assertEquals($expected, $encode);
    }

    public function testToString()
    {
        $message = new Message();
        $message->status = 200;
        $message->setHeader("A", 'B');
        $message->body = "body";
        $len = strlen($message->body);
        $expected = "HTTP/1.1 {$message->status} {$this->map[$message->status]}\r\nA: B\r\ncontent-length: {$len}\r\n\r\n{$message->body}";
        $this->assertEquals($expected, strval($message));
    }

    public function testDecodeHeaders()
    {
        /** @var Message $expected */

        $expected = new Message();
        $expected->status = 200;
        $expected->setHeader("a", 'B');
        $expected->body = "body";
        $expected->setHeader("content-length", strlen($expected->body));
        $buf = "HTTP/1.1 {$expected->status} {$this->map[$expected->status]}\r\nA: B\r\ncontent-length: {$expected->getHeader("content-length")}\r\n\r\n{$expected->body}";
        list($actual, $offset) = Message::decode($buf);
        $this->assertEquals($expected, $actual);
        $this->assertEquals(strlen($buf), $offset);

        $buf = "";
        list($actual, $offset) = Message::decode($buf);
        $this->assertEquals(null, $actual);
        $this->assertEquals(0, $offset);

        $expected = new Message();
        $expected->status = 200;
        $expected->setHeader("a", 'B');
        $expected->setHeader("content-length", strlen($expected->body));
        $buf = "HTTP/1.1 {$expected->status} {$this->map[$expected->status]}\r\nA: B\r\ncontent-length: 0\r\n\r\n";
        list($actual, $offset) = Message::decode($buf);
        $this->assertEquals($expected, $actual);
        $this->assertEquals(strlen($buf), $offset);

        $expected = new Message();
        $expected->status = 200;
        $expected->body = "body";
        $expected->setHeader("a", 'B');
        $expected->setHeader("content-length", strlen($expected->body));
        $buf = "HTTP/1.1 {$expected->status} {$this->map[$expected->status]}\r\nA: B\r\ncontent-length: 1110\r\n\r\n";
        list($actual, $offset) = Message::decode($buf);
        $this->assertEquals(null, $actual);
        $this->assertEquals(0, $offset);

        $expected = new Message();
        $expected->status = 200;
        $expected->setHeader("a", 'B');
        $expected->body = "body";
        $expected->setHeader("content-length", strlen($expected->body));
        $buf = str_repeat("a", 6) . "HTTP/1.1 {$expected->status} {$this->map[$expected->status]}\r\nA: B\r\ncontent-length: {$expected->getHeader("content-length")}\r\n\r\n{$expected->body}";
        list($actual, $offset) = Message::decode($buf, 6);
        $this->assertEquals($expected, $actual);
        $this->assertEquals(strlen($buf), $offset);

//        $start < 0
//        $expected = new Message();
//        $expected->status = 200;
//        $expected->setHeader("a", 'B');
//        $expected->body = "body";
//        $expected->setHeader("content-length", strlen($expected->body));
//        $buf = "HTTP/1.1 {$expected->status} {$this->map[$expected->status]}\r\nA: B\r\ncontent-length: {$expected->getHeader("content-length")}\r\n\r\n{$expected->body}";
//        list($actual, $offset) = Message::decode($buf, -1);
//        $this->assertEquals($expected, $actual);
//        $this->assertEquals(strlen($buf), $offset);

        $expected = new Message();
        $expected->setHeader("a", "b");
        $expected->setHeader("content-length", 0);
        $buf = "GET / HTTP/1.1\r\na: b\r\ncontent-length: 0\r\n\r\n";
        list($actual, $offset) = Message::decode($buf);
        $this->assertEquals(strlen($buf), $offset);

        $expected = new Message();
        $expected->setHeader("a", "b");
        $expected->setHeader("content-length", 0);
        $buf = "GET / HTTP/1.1\r\na: b\r\nc\r\ncontent-length: 0\r\n\r\n";
        list($actual, $offset) = Message::decode($buf);
        $this->assertEquals($expected, $actual);
        $this->assertEquals(strlen($buf), $offset);
    }
}
