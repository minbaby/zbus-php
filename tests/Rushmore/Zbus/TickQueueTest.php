<?php

namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\TickQueue;
use Test\TestCase;

/**
 * Class TickQueueTest
 * @package Test\Rushmore\Zbus
 */
class TickQueueTest extends TestCase
{
    /**
     * @var TickQueue
     */
    private $instance;

    public function testAddAndEmpty()
    {
        $this->assertTrue($this->instance->isEmpty());

        $this->instance->add($this->getEchoCallback("YES"));
        $this->assertFalse($this->instance->isEmpty());

        return $this->instance;
    }

    /**
     * @depends testAddAndEmpty
     * @param TickQueue $tickQueue
     */
    public function testTick(TickQueue $tickQueue)
    {
        $this->assertFalse($tickQueue->isEmpty());
        $tickQueue->tick();
        $this->assertTrue($tickQueue->isEmpty());

        $this->expectOutputString("YES");
    }

    public function testConstruct()
    {
        /** @var \SplQueue $queue */
        $queue = $this->getProperty($this->instance, 'queue');

        $this->assertInstanceOf(\SplQueue::class, $queue);

        $this->assertEquals(0, count($queue));

        $this->assertTrue($this->instance->isEmpty());

        $this->assertTrue($this->getRefClass($this->instance)->isFinal());
    }

    protected function setUp()
    {
        $this->instance = new TickQueue();
    }
}
