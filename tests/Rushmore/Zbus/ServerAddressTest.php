<?php

namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\ServerAddress;
use Test\TestCase;

class ServerAddressTest extends TestCase
{

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage address not support
     */
    public function testConstructAndToString()
    {
        $address = "127.0.0.1";

        $serverAddress = new ServerAddress($address);
        $this->assertEquals($address, $serverAddress->address);
        $this->assertFalse($serverAddress->ssl_enabled);
        $this->assertEquals($address, strval($serverAddress));

        $serverAddress = new ServerAddress($address, true);
        $this->assertEquals($address, $serverAddress->address);
        $this->assertTrue($serverAddress->ssl_enabled);
        $this->assertEquals("[SSL]" . $address, strval($serverAddress));

        $serverAddress = new ServerAddress([
            'address' => $address,
            'sslEnabled' => false
        ], true);
        $this->assertEquals($address, $serverAddress->address);
        $this->assertFalse($serverAddress->ssl_enabled);

        $serverAddress1 = new ServerAddress($serverAddress, true);
        $this->assertEquals($serverAddress, $serverAddress1);

        $serverAddress = new ServerAddress(new \stdClass());
    }
}
