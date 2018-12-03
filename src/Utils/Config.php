<?php

namespace Weiche\Scheduler\Utils;

use Weiche\Scheduler\Exception\FileNotFoundException;

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
     * 初始化配置文件（不包括工作流配置）
     */
    public static function init()
    {
        if (self::$config) {
            return;
        }

        $configPath = APP_PATH . '/Config';

        $commonCfg = include_once($configPath . '/common.php');
        $envCfg = include_once($configPath . '/' . strtolower(ENV) . '.php');

        // server 的配置特殊处理
        self::$config['server'] = array_replace($commonCfg['server'], $envCfg['server'] ?: []);
        unset($commonCfg['server'], $envCfg['server']);

        self::$config += array_replace($commonCfg, $envCfg);
    }

    /**
     * 获取配置信息（不包括工作流的配置）
     * 多层次信息用 . 隔开
     *
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    public static function get($key = '', $default = null)
    {
        if (!static::$config) {
            self::init();
        }

        if (!$key) {
            return self::$config;
        }

        $keyNodes = explode('.', $key);

        $cfg = self::$config;
        foreach ($keyNodes as $node) {
            $cfg =  is_array($cfg) && array_key_exists($node, $cfg) ? $cfg[$node] : null;
        }

        return $cfg === null && $default !== null ? $default : $cfg;
    }

    /**
     * 加载工作流配置
     * @param $name
     * @throws FileNotFoundException
     * @return array
     */
    public static function workflow($name)
    {
        static $workflows = [];

        if (!$workflows[$name]) {
            $file = APP_PATH . "/Config/workflow/{$name}.php";
            if (!file_exists($file)) {
                throw new FileNotFoundException("工作流配置文件{$file}不存在");
            }

            $workflows[$name] = include_once($file);
        }

        return $workflows[$name];
    }
}
