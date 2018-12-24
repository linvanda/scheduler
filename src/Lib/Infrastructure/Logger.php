<?php

namespace Scheduler\Infrastructure;

use Monolog\Logger;

/**
 * 日志
 * Class Logger
 * @package Scheduler\Infrastructure
 */
class Logger
{
    protected static $logger;

    public static function init()
    {
        if (!self::$logger) {
            self::$logger = new Logger("scheduler");
        }
    }
}
