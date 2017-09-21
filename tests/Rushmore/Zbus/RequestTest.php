<?php

namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\Request;
use Test\TestCase;

class RequestTest extends TestCase
{
    public function testConstruct()
    {
        $instance = new Request('method', 'params', 'module');

        $this->assertEquals($instance->method, 'method');
        $this->assertEquals($instance->params, 'params');
        $this->assertEquals($instance->module, 'module');
    }
}
