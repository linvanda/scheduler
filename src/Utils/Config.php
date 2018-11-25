<?php

namespace Weiche\Scheduler\Utils;

/**
 * 配置解析类
 *
 * Class Config
 * @package Weiche\Scheduler\Utils
 */
class Config
{
    private static $config;

    /**
     * 获取配置信息（不包括工作流的配置）
     *
     * @param string $key
     */
    public static function config($key = '')
    {
        if (!static::$config) {
            // 解析配置数组

        }
    }
}
