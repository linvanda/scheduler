<?php

namespace Scheduler\Context;
use Swoole\Channel;

/**
 * 整个服务全局上下文，该上下文中的变量全都是共享内存
 * Class GContext
 */
class GContext
{
    private static $instance;
    // 日志通道
    private $loggerChannel;

    private function __construct()
    {
        $this->loggerChannel = new Channel(1024 * 1024 * 80);
    }

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
    }

    /**
     * 全局日志通道
     * @return Channel
     */
    public static function loggerChannel()
    {
        return self::$instance->loggerChannel;
    }

    public static function destroy()
    {
        if (self::$instance) {
            self::$instance = null;
        }
    }
}
