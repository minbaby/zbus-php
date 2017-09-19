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

        $this->assertNotEquals($time, $timers->getTime());
    }

//    public function testContains()
//    {
//        $timers = new Timers();
//
//        $timer = new Timer(1, function () {}, true);
//    }
}