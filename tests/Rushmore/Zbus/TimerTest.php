<?php

namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\Timer;
use Test\TestCase;

class TimerTest extends TestCase
{
    protected function getCallback()
    {
        return function () {
            return __LINE__;
        };
    }

    public function testMinInterval()
    {
        $min = 0.0000001;
        $timer = new Timer($min, $this->getCallback(), true);
        $this->assertLessThan(Timer::MIN_INTERVAL, $min);
        $this->assertEquals($timer->getInterval(), Timer::MIN_INTERVAL);

        $excepted = 0.1;
        $timer = new Timer($excepted, $this->getCallback(), true);
        $this->assertEquals($timer->getInterval(), $excepted);
    }

    public function testPeriodic()
    {
        $timer = new Timer(Timer::MIN_INTERVAL, $this->getCallback(), true);
        $this->assertTrue($timer->isPeriodic());

        $timer = new Timer(Timer::MIN_INTERVAL, $this->getCallback(), false);
        $this->assertFalse($timer->isPeriodic());
    }

    /**
     * @expectedException \TypeError
     */
    public function testGetCallBack()
    {
        $timer = new Timer(Timer::MIN_INTERVAL, $this->getCallback(), true);
        $this->assertEquals($timer->getCallback()(), $this->getCallback()());

        //throw TypeError
        new Timer(Timer::MIN_INTERVAL, null, true);
    }

    public function testIsFinal()
    {
        $reflection = new \ReflectionClass(Timer::class);
        $this->assertTrue($reflection->isFinal());
    }
}