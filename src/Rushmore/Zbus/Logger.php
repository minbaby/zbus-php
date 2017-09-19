<?php

namespace Rushmore\Zbus;

class Logger {
    const DEBUG = 0;
    const INFO = 1;
    const WARN = 2;
    const ERROR = 3;

    public static $Level = Logger::DEBUG;

    public static function log($level, $message){
        if($level < Logger::$Level) return;
        error_log($message);
    }

    public static function debug($message){
        Logger::log(Logger::DEBUG, $message);
    }
    public static function info($message){
        Logger::log(Logger::INFO, $message);
    }
    public static function warn($message){
        Logger::log(Logger::WARN, $message);
    }
    public static function error($message){
        Logger::log(Logger::ERROR, $message);
    }
}