<?php

namespace Test;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    protected function getProperty($obj, $property)
    {
        $ref = new \ReflectionClass($obj);

        $property = $ref->getProperty($property);
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }

        return $property->getValue($obj);
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
