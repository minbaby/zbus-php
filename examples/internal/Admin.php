<?php

use Rushmore\Zbus\Broker;
use Rushmore\Zbus\EventLoop;
use Rushmore\Zbus\Logger;
use Rushmore\Zbus\Mq\MqAdmin;

require_once __DIR__ . '/../../vendor/autoload.php';

Logger::$Level = Logger::DEBUG; //change to info to disable verbose message

$loop = new EventLoop();
$broker = new Broker($loop, "localhost:15555;localhost:15556");

$broker->on('ready', function() use($loop, $broker){

	$admin = new MqAdmin($broker);
	$res = $admin->queryAsync("MyTopic", function($data) use($broker){
		if(is_a($data, 'Exception')){
			echo $data->getMessage() . PHP_EOL;
		} else {
			echo json_encode($data) . PHP_EOL;
		}

		$broker->close();
	});

});

$loop->runOnce();