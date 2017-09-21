<?php

namespace Test\Rushmore\Zbus;

use Mockery;
use Rushmore\Zbus\Logger;
use Test\TestCase;

class LoggerTest extends TestCase
{
    const YES = 'YES';
    /**
     * @var \Mockery\MockInterface
     */
    public static $functions;

    public function testLog()
    {
        self::$functions->shouldReceive('error_log')->with(static::YES)->andReturn(static::YES)->once();

        Logger::$Level = Logger::INFO;
        Logger::log(Logger::DEBUG, static::YES);
        $this->expectOutputString(static::YES);

        Logger::$Level = Logger::INFO;
        Logger::log(Logger::ERROR, static::YES);
        $this->expectOutputString(static::YES);
    }

    public function testDebug()
    {
        self::$functions->shouldReceive('error_log')->with(static::YES)->andReturn(static::YES)->once();

        Logger::$Level = Logger::DEBUG;
        Logger::debug(static::YES);
        $this->expectOutputString(static::YES);

        Logger::$Level = Logger::INFO;
        Logger::debug(static::YES);
        $this->expectOutputString(static::YES);

        Logger::$Level = Logger::WARN;
        Logger::debug(static::YES);
        $this->expectOutputString(static::YES);

        Logger::$Level = Logger::ERROR;
        Logger::debug(static::YES);
        $this->expectOutputString(static::YES);
    }

    public function testInfo()
    {
        self::$functions->shouldReceive('error_log')->with(static::YES)->andReturn(static::YES)->twice();

        Logger::$Level = Logger::DEBUG;
        Logger::info(static::YES);
        $this->expectOutputString(static::YES);

        Logger::$Level = Logger::INFO;
        Logger::info(static::YES);
        $this->expectOutputString(str_repeat(static::YES, 2));

        Logger::$Level = Logger::WARN;
        Logger::info(static::YES);
        $this->expectOutputString(str_repeat(static::YES, 2));

        Logger::$Level = Logger::ERROR;
        Logger::info(static::YES);
        $this->expectOutputString(str_repeat(static::YES, 2));
    }

    public function testWarn()
    {
        self::$functions->shouldReceive('error_log')->with(static::YES)->andReturn(static::YES)->times(3);

        Logger::$Level = Logger::DEBUG;
        Logger::warn(static::YES);
        $this->expectOutputString(static::YES);

        Logger::$Level = Logger::INFO;
        Logger::warn(static::YES);
        $this->expectOutputString(str_repeat(static::YES, 2));

        Logger::$Level = Logger::WARN;
        Logger::warn(static::YES);
        $this->expectOutputString(str_repeat(static::YES, 3));

        Logger::$Level = Logger::ERROR;
        Logger::warn(static::YES);
        $this->expectOutputString(str_repeat(static::YES, 3));
    }

    public function testError()
    {
        self::$functions->shouldReceive('error_log')->with(static::YES)->andReturn(static::YES)->times(4);

        Logger::$Level = Logger::DEBUG;
        Logger::error(static::YES);
        $this->expectOutputString(static::YES);

        Logger::$Level = Logger::INFO;
        Logger::error(static::YES);
        $this->expectOutputString(str_repeat(static::YES, 2));

        Logger::$Level = Logger::WARN;
        Logger::error(static::YES);
        $this->expectOutputString(str_repeat(static::YES, 3));

        Logger::$Level = Logger::ERROR;
        Logger::error(static::YES);
        $this->expectOutputString(str_repeat(static::YES, 4));
    }

    protected function setUp()
    {
        self::$functions = Mockery::mock();
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}




namespace Rushmore\Zbus;

use Test\Rushmore\Zbus\LoggerTest as LT;

/**
 * 重写 Rushmore\Zbus 下的方法
 *
 * @param $msg
 */
function error_log($msg)
{
    echo LT::$functions->error_log($msg);
}
