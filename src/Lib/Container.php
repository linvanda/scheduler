<?php

namespace Scheduler;

use DI\ContainerBuilder;
use function DI\create;
use Scheduler\Server\CoroutineServer;
use Scheduler\Utils\Config;

/**
 * 容器
 * Class Container
 * @package Scheduler
 */
class Container
{
    /** @var \DI\Container */
    private static $di;

    protected function __construct()
    {
    }

    /**
     * 代理方法：get
     * @param $name
     * @return mixed
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Exception
     */
    public static function get($name)
    {
        return self::inst()->get($name);
    }

    /**
     * 代理方法：make
     * @param $name
     * @param $params
     * @return mixed
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Exception
     */
    public static function make($name, $params)
    {
        return self::inst()->make($name, $params);
    }

    /**
     * @return \DI\Container
     * @throws \Exception
     */
    public static function inst()
    {
        if (!self::$di) {
            $builder = new ContainerBuilder();

            if (ENV === ENV_PRODUCTION) {
                $builder->enableCompilation(DATA_PATH . '/di');
                $builder->writeProxiesToFile(true, DATA_PATH . '/di/proxies');
            }

            $builder->addDefinitions(self::config());
            self::$di = $builder->build();
        }

        return self::$di;
    }

    /**
     * 这里配置注入信息
     * @throws
     */
    private static function config()
    {
        return array_map(
            function ($class) {
                return create($class);
            },
            array_replace(
                [
                    'Server' => CoroutineServer::class
                ],
                Config::di()
            )
        );
    }
}
