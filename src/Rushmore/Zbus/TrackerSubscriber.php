<?php

namespace Rushmore\Zbus;

class TrackerSubscriber
{
    public $client;
    public $readyCount = 0;
    public $readyTriggered = false;

    public function __construct($client)
    {
        $this->client = $client;
    }
}
