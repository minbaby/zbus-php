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



    //TODO
    public function testGetFirst()
    {
        $timers= new Timers();

        $this->assertNull($timers->getFirst());


        $timer1 = new Timer(1, function () {
        }, true);
        $microtime1 = microtime(true) + $timer1->getInterval();
        $timers->add($timer1);

        $timer2 = new Timer(2, function () {
        }, true);
        $microtime2 = microtime(true);
        $timers->add($timer2);

        $timer3 = new Timer(3, function () {
        }, true);
        $microtime3 = microtime(true);
        $timers->add($timer3);

        $this->assertLessThanOrEqual($timers->getFirst(), $microtime2);
        $this->assertLessThanOrEqual($timers->getFirst(), $microtime2);


    }
}