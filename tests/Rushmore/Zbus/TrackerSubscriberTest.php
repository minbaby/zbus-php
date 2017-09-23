<?php
namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\TrackerSubscriber;
use Test\TestCase;

class TrackerSubscriberTest extends TestCase
{
    public function testConstruct()
    {
        $trackerSubscriber = new TrackerSubscriber("client");
        $this->assertEquals("client", $trackerSubscriber->client);
    }
}
