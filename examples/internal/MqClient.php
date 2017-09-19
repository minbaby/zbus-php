<?php

use Rushmore\Zbus\Logger;
use Rushmore\Zbus\Message;
use Rushmore\Zbus\Mq\MqClient;

require_once __DIR__ . '/../../vendor/autoload.php';
 
Logger::$Level = Logger::INFO;

$client = new MqClient("localhost:15555");

$msg = new Message();
$msg->topic = "MyTopic";
$msg->body = "From PHP sync";

$res = $client->produce($msg);
echo $res . PHP_EOL;
