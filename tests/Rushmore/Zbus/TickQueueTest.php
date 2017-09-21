<?php

namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\TickQueue;
use Test\TestCase;

/**
 * TODO
 *
 * Class TickQueueTest
 * @package Test\Rushmore\Zbus
 */
class TickQueueTest extends TestCase
{
    private $instance;

    public function testAdd()
    {

    }

    protected function setUp()
    {
        $this->instance = new TickQueue();
    }
}
