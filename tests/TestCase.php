<?php

namespace Test;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Rushmore\Zbus\Logger;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    protected $zbusServer = '127.0.0.1:15555';

    protected function getProperty($obj, $property)
    {
        $ref = $this->getRefClass($obj);

        $property = $ref->getProperty($property);
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }

        return $property->getValue($obj);
    }

    protected function getMethod($obj, $method)
    {
        $ref = $this->getRefClass($obj);

        return $ref->getMethod($method);
    }

    protected function getRefClass($obj)
    {
        return new \ReflectionClass($obj);
    }

    protected function getReturnCallback($value)
    {
        return function () use ($value) {
            return $value;
        };
    }

    protected function getEchoCallback($value)
    {
        return function () use ($value) {
            echo $value . implode(func_get_args());
        };
    }
}
