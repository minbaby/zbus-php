<?php

use Rushmore\Zbus\Broker;
use Rushmore\Zbus\Consumer;
use Rushmore\Zbus\EventLoop;
use Rushmore\Zbus\Logger;

require_once __DIR__ . '/../../vendor/autoload.php';

$messageHandler = function ($msg, $client) { //where you should focus on
    echo $msg . PHP_EOL;
};


Logger::$Level = Logger::DEBUG; //change to info to disable verbose message
$loop = new EventLoop();
$broker = new Broker($loop, "localhost:15555;localhost:15556"); //HA, test it?!
$c = new Consumer($broker, 'MyTopic');
$c->connectionCount = 2;
$c->messageHandler = $messageHandler;

$c->start();
$loop->run();
