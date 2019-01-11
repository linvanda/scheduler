<?php

namespace Scheduler\Fundation;

use DI\ContainerBuilder;
use function DI\create;
use Monolog\Handler\StreamHandler;
use Scheduler\Server\CoroutineServer;
use Scheduler\Utils\Config;
use \Monolog\Logger;

/**
 * 容器
 * Class Container
 * @package Scheduler\Fundation
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
     * @param string $name
     * @return mixed
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Exception
     */
    public static function get(string $name)
    {
        return self::$di->get($name);
    }

    public static function set(string $name, $value)
    {
        self::$di->set($name, is_string($value) ? create($value) : $value);
    }

    /**
     * 代理方法：make
     * @param string $name
     * @param $params
     * @return mixed
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Exception
     */
    public static function make(string $name, $params)
    {
        return self::$di->make($name, $params);
    }

    /**
     * @throws \Exception
     */
    public static function init()
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
    }

    /**
     * 这里配置注入信息
     * @throws
     */
    private static function config()
    {
        return array_map(
            function ($item) {
                return is_string($item) ? create($item) : $item;
            },
            array_replace(
                [
                    'Server' => CoroutineServer::class,
                    'Router' => Router::class,
                    'Logger' => function () {
                        $logger = new Logger("app");
                        $logger->pushHandler(new StreamHandler(DATA_PATH . '/log/app.log'));

                        return $logger;
                    }
                ],
                Config::di()
            )
        );
    }
}
