<?php

namespace Scheduler\Utils;

use Scheduler\Exception\FileNotFoundException;

/**
 * 配置解析类
 *
 * Class Config
 * @package Scheduler\Utils
 */
class Config
{
    private static $config = [];

    /**
     * 获取配置信息
     * key 完成格式：dir/filename:part1.part2.part3, dir相对于Config/目录，filename 默认是 common + ENV
     *
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     * @throws FileNotFoundException
     */
    public static function get($key = '', $default = null)
    {
        list($module, $key) = explode(':', strpos($key, ':') === false ? "common:$key" : $key);

        $cfg = self::module($module);

        if (!$key) {
            return $cfg;
        }

        $keyNodes = explode('.', $key);

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
        return self::get("workflow/$name:");
    }

    /**
     * 子系统配置
     * @param int|string $systemIdOrAlias 子系统id或者别名
     * @return array
     * @throws FileNotFoundException
     */
    public static function subSystem($systemIdOrAlias)
    {
        $subSystem = self::get("subsystem:");

        if (is_string($systemIdOrAlias) && strlen($systemIdOrAlias) === 2) {
            return $subSystem[$systemIdOrAlias];
        } elseif ($subSystem[$systemIdOrAlias]) {
            return $subSystem[$subSystem[$systemIdOrAlias]];
        } else {
            foreach ($subSystem as $alias => $system) {
                if ($system['app_id'] == $systemIdOrAlias) {
                    $subSystem[$systemIdOrAlias] = $alias;
                    return $system;
                }
            }
        }

        return [];
    }


    /**
     * 初始化配置文件
     * @param string $module
     * @return array
     * @throws FileNotFoundException
     */
    private static function module($module = 'common')
    {
        if (self::$config[$module]) {
            return self::$config[$module];
        }

        list($dir, $module) = explode('/', strpos($module, '/') === false ? "/$module" : $module);

        $configPath = APP_PATH . "/Config/$dir";
        $file = $configPath . "/$module.php";

        if (!file_exists($file)) {
            throw new FileNotFoundException("配置文件{$file}不存在");
        }

        if ($module === 'common') {
            $envFile = $configPath . '/' . strtolower(ENV) . '.php';

            if (!file_exists($envFile)) {
                throw new FileNotFoundException("配置文件{$envFile}不存在");
            }

            $commonCfg = include($file);
            $envCfg = include($envFile);

            // server 的配置特殊处理
            $config['server'] = array_replace($commonCfg['server'], $envCfg['server'] ?: []);
            unset($commonCfg['server'], $envCfg['server']);

            // 其它配置项使用直接覆盖
            $config += array_replace($commonCfg, $envCfg);

            self::$config[$module] = $config;
        } else {
            self::$config[$module] = include($file);
        }

        return self::$config[$module];
    }
}
