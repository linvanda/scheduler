<?php

namespace Scheduler\Infrastructure;
use Swoole\Process;

/**
 * 日志包装器
 * 该类和 LoggerCollector 配合使用
 * Class Logger
 * @package Scheduler\Infrastructure
 */
class Logger
{
    const MAX_MSG_LENGTH = 32 * 1024 * 1024;

    /**
     * @var Process
     */
    private static $process;

    public static function init(Process $process) {
        self::$process = $process;
    }

    public static function debug($message, array $context = [])
    {
        self::send('debug', $message, $context);
    }

    public static function info($message, array $context = [])
    {
        self::send('info', $message, $context);
    }

    public static function notice($message, array $context = [])
    {
        self::send('notice', $message, $context);
    }

    public static function warning($message, array $context = [])
    {
        self::send('warning', $message, $context);
    }

    public static function error($message, array $context = [])
    {
        self::send('error', $message, $context);
    }

    public static function critical($message, array $context = [])
    {
        self::send('critical', $message, $context);
    }

    public static function alert($message, array $context = [])
    {
        self::send('alert', $message, $context);
    }

    public static function emergency($message, array $context = [])
    {
        self::send('emergency', $message, $context);
    }

    /**
     * @param string $level
     * @param string $message 长度不超过 32k
     * @param array $context
     */
    private static function send(string $level, string $message, array $context = [])
    {
        if (strlen($message) > self::MAX_MSG_LENGTH) {
            $message = substr($message, 0, self::MAX_MSG_LENGTH);
        }

        if (strlen(json_encode($context)) > self::MAX_MSG_LENGTH) {
            $context = [];
        }

        self::$process->push(json_encode([
            'level' => $level,
            'message' => $message,
            'context' => $context
        ], JSON_UNESCAPED_UNICODE));
    }
}
