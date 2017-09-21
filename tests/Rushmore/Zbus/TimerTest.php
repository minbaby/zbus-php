<?php

namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\Timer;
use Test\TestCase;

class TimerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }


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

    /**
     * @expectedException \Exception
     */
    public function testGetCallBack()
    {
        $timer = new Timer(Timer::MIN_INTERVAL, $this->getCallback(), true);
        $actual = $timer->getCallback();
        $excepted = $this->getCallback();
        $this->assertTrue(is_callable($actual));
        $this->assertEquals($actual(), $excepted());

        // php >= 7 throw TypeError
        // php < 7 fatal error ==> PHPUnit_Framework_Error
        try {
            new Timer(Timer::MIN_INTERVAL, null, true);
        } catch (\PHPUnit_Framework_Error $exception) {
            throw new \Exception("error");
        } catch (\TypeError $exception) {
            throw new \Exception("error");
        }
    }

    public function testPeriodic()
    {
        $timer = new Timer(Timer::MIN_INTERVAL, $this->getCallback(), true);
        $this->assertTrue($timer->isPeriodic());

        $timer = new Timer(Timer::MIN_INTERVAL, $this->getCallback(), false);
        $this->assertFalse($timer->isPeriodic());
    }

    public function testIsFinal()
    {
        $reflection = new \ReflectionClass(Timer::class);
        $this->assertTrue($reflection->isFinal());
    }
}
