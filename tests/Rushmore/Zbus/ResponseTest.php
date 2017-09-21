<?php

namespace Test\Rushmore\Zbus;

use Rushmore\Zbus\Response;
use Test\TestCase;

class ResponseTest extends TestCase
{
    public function testConstruct()
    {
        $instance = new Response('result', 'error');
        $this->assertEquals($instance->result, 'result');
        $this->assertEquals($instance->error, 'error');

        return $instance;
    }

    /**
     * @depends testConstruct
     * @param Response $response
     */
    public function testToString(Response $response)
    {
        $this->assertEquals("{\"result\":\"result\",\"error\":\"error\"}", strval($response));
    }
}
