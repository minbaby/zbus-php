<?php

use Rushmore\Zbus\Broker;
use Rushmore\Zbus\EventLoop;
use Rushmore\Zbus\Message;
use Rushmore\Zbus\Producer;

require_once __DIR__ . '/../../vendor/autoload.php';


function biz($broker)
{
    $producer = new Producer($broker);
    $msg = new Message();
    $msg->topic = 'MyTopic';
    $msg->body = 'From PHP sync';
    
    $res = $producer->publish($msg);
    echo $res . PHP_EOL;
}


$loop = new EventLoop();
$broker = new Broker($loop, "localhost:15555;localhost:15556", true); // enable sync mode

$broker->on('ready', function () use ($loop, $broker) {
    //run after ready
    try {
        biz($broker);
    } catch (Exception $e) {
        echo $e->getMessage() . PHP_EOL;
    }
    
    $broker->close();
    $loop->stop(); //stop anyway
});

$loop->runOnce();
