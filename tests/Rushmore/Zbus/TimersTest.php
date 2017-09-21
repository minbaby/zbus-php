<?php

namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\Timer;
use Rushmore\Zbus\Timers;
use Test\TestCase;

/**
 * TODO
 *
 * Class TimersTest
 * @package Test\Rushmore\Zbus
 */
class TimersTest extends TestCase
{
    public function testIsFinal()
    {
        $reflection = new \ReflectionClass(Timers::class);
        $this->assertTrue($reflection->isFinal());
    }

    public function testUpdateTimeAndGetTime()
    {
        $timers = new Timers();

        $time = $timers->getTime();
        $timers->updateTime();

        usleep(1000);

        $this->assertNotEquals($time, $timers->getTime());
    }

    public function testContainsAndAddAndCancel()
    {
        $timers = new Timers();

        $timer = new Timer(1, function () {
        }, true);

        $timers->add($timer);

        $this->assertTrue($timers->contains($timer));
        $timers->cancel($timer);
        $this->assertFalse($timers->contains($timer));

        $timer = new Timer(1, function () {
        }, true);

        $this->assertFalse($timers->contains($timer));
    }

    public function testCancel()
    {
        $timers = new Timers();

        $timer = new Timer(1, function () {
        }, true);

        $timers->add($timer);

        $this->assertTrue($timers->contains($timer));
        $timers->cancel($timer);
        $this->assertFalse($timers->contains($timer));
    }

    public function testIsEmpty()
    {
        $timers = new Timers();

        $this->assertTrue($timers->isEmpty());

        $timer = new Timer(1, function () {
        }, true);

        $timers->add($timer);

        $this->assertFalse($timers->isEmpty());
    }

    public function testGetFirst()
    {
        $timers = new Timers();

        $this->assertNull($timers->getFirst());

        $timer1 = new Timer(1, function () {
        }, true);

        $ref = new \ReflectionClass($timers);

        // 反射 $scheduler 特殊情况处理
        $schedulerProperty = $ref->getProperty('scheduler');
        $schedulerProperty->setAccessible(true);

        $this->addTimerToTimers($timers, $timer1, -1, null, true, false);
        $this->assertNull($timers->getFirst());

        $current = microtime(true);
        $scheduledAtList = [];

        foreach (range(0, 10) as $i) {
            $timer = new Timer($i, function () {
            }, true);
            $scheduledAtList[] = $scheduledAt = $timer->getInterval() + $current;
            $this->addTimerToTimers($timers, $timer, -$scheduledAt, $scheduledAt);
        }

        $this->assertEquals($scheduledAtList[0], $timers->getFirst());
    }

    public function testTick()
    {
        $str = '';
        $once = 'callback-one-time';
        $every = 'callback-every-time';

        $timers = new Timers();

        $timer1 = new Timer(0.01, function () use ($once) {
            echo $once;
        }, false);

        $timer2 = new Timer(0.02, function () use ($every) {
            echo $every;
        }, true);

        $timers->add($timer1);
        $timers->add($timer2);

        usleep(1000 * 1000 * $timer1->getInterval());
        $timers->tick();
        $this->expectOutputString($str .= $once);

        $i = 0;
        while ($i < 10) {
            usleep(1000 * 1000 * $timer2->getInterval());
            $timers->tick();
            $this->resetCount();
            $this->expectOutputString($str .= $every);
            $i++;
        }

        $timers1 = new Timers();

        $timer3 = new Timer(0.02, function () use ($every) {
            echo $every;
        }, true);


        $this->addTimerToTimers($timers1, $timer3, null, null, true, false);

        usleep(1000 * 1000 * $timer3->getInterval());

        $timers1->tick();
        $this->assertTrue($timers1->isEmpty());
    }

    /**
     * @param Timers $timers
     * @param Timer $time
     * @param $schedulerValue
     * @param $timersValue
     * @param $doSchedulerValue
     * @param $doTimersValue
     */
    protected function addTimerToTimers(
        Timers $timers,
        Timer $time,
        $schedulerValue,
        $timersValue,
        $doSchedulerValue = true,
        $doTimersValue = true
    ) {
        $ref = new \ReflectionClass($timers);

        if ($doSchedulerValue) {
            // 反射 $scheduler 特殊情况处理
            $schedulerProperty = $ref->getProperty('scheduler');
            $schedulerProperty->setAccessible(true);

            $schedulerProperty->getValue($timers)->insert($time, $schedulerValue);
        }


        if ($doTimersValue) {
            // 反射 $timers
            $timersProperty = $ref->getProperty('timers');
            $timersProperty->setAccessible(true);
            if ($timersValue == null) {
                $timersProperty->getValue($timers)->attach($time);
            } else {
                $timersProperty->getValue($timers)->attach($time, $timersValue);
            }
        }
    }
}
