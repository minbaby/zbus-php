<?php
namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\EventLoop;
use Rushmore\Zbus\Stream;
use Test\TestCase;

//TODO
class StreamTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstruct()
    {
        $eventLoop = new EventLoop();

        new Stream("", $eventLoop, null, null);

//        \Mockery::mock();

        //
        $stream = \Mockery::mock();
        $stream = new Stream("", $eventLoop, null, null);

    }
}