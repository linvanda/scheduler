<?php

namespace Scheduler\Fundation;
use Scheduler\Context\GContext;
use Swoole\Coroutine;

/**
 * 日志包装器
 * 该类和 LoggerCollector 配合使用
 * Class Logger
 * @package Scheduler\Fundation
 */
class Logger
{
    const MAX_MSG_LENGTH = 32 * 1024 * 1024;

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
     * 记录日志：直接发送到全局的日志队列中
     * 协程环境下，如果发送失败（通道满了），则进入协程等待，非协程环境则直接返回（此时sleep会阻塞整个进程）
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

        $retry = Coroutine::getuid() < 0 ? 0 : 3;

        $i = 0;
        do {
            $result = GContext::loggerChannel()->push([
                'level' => $level,
                'message' => $message,
                'context' => $context
            ]);

            if (!$result && $retry) {
                Coroutine::sleep(1);
            } else {
                break;
            }
        } while ($i++ < $retry);
    }
}
