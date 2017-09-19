<?php

use Rushmore\Zbus\Broker;
use Rushmore\Zbus\EventLoop;
use Rushmore\Zbus\Logger;
use Rushmore\Zbus\Request;
use Rushmore\Zbus\Rpc\RpcInvoker;

require_once __DIR__ . '/../../vendor/autoload.php';


function biz($loop, $broker)
{
    $rpc = new RpcInvoker($broker, "MyRpc");
    
    //1) Raw invocation
    $req = new Request("plus", [1, 2]);
    $rpc->invokeAsync($req, function ($res) use ($broker) {
        echo $res->result . PHP_EOL;
    });
        
    //2) strong typed
    $rpc->plus(1, 2, function ($res) use ($broker, $loop) {
        echo $res . PHP_EOL;
        
        $broker->close();
    });
}

Logger::$Level = Logger::INFO;
$loop = new EventLoop();
$broker = new Broker($loop, "localhost:15555;localhost:15556", false);
$broker->on('ready', function () use ($loop, $broker) {
    biz($loop, $broker);
});

$loop->runOnce();
