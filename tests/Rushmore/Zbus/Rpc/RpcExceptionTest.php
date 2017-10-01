<?php

namespace Test\Rushmore\Zbus\Rpc;

use Rushmore\Zbus\Rpc\RpcException;
use Test\TestCase;

class RpcExceptionTest extends TestCase
{
    public function testToString()
    {
        $ex = new RpcException("message", 110);
        $this->assertEquals("Rushmore\Zbus\Rpc\RpcException: [110]: message\n", strval($ex));
    }
}
