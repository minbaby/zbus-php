<?php

use Rushmore\Zbus\Broker;
use Rushmore\Zbus\EventLoop;
use Rushmore\Zbus\Message;
use Rushmore\Zbus\Producer;

require_once __DIR__ . '/../../vendor/autoload.php';


function biz($loop, $broker)
{
    $producer = new Producer($broker);
    $msg = new Message();
    $msg->topic = 'MyTopic';
    $msg->body = 'From PHP async';
    
    $producer->publishAsync($msg, function ($msg) use ($broker) {
        echo $msg . PHP_EOL;
        $broker->close(); //must close to exit async loop!!
    });
}


$loop = new EventLoop();
$broker = new Broker($loop, "localhost:15555;localhost:15556", false); // disable sync mode
$broker->on('ready', function () use ($loop, $broker) {
    biz($loop, $broker);
});
$loop->runOnce();
