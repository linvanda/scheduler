<?php

namespace Weiche\Scheduler;

use DI\ContainerBuilder;
use function DI\create;
use function DI\factory;
use Weiche\Scheduler\Infrastructure\Signer\WeicheSigner;
use Weiche\Scheduler\Infrastructure\WeicheRouter;
use Weiche\Scheduler\Server\CoroutineServer;
use Weiche\Scheduler\Server\TaskServer;
use Weiche\Scheduler\Utils\Config;
use Weiche\Scheduler\Server\Server;

/**
 * 容器包装器
 * Class Container
 * @package Weiche\Scheduler
 */
class Container
{
    /** @var \DI\Container */
    private static $di;

    protected function __construct()
    {
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
     */
    private static function config()
    {
        return [
            // 服务器
            'Server' => Config::get('work_type') === 'task' ? create(TaskServer::class) : create(CoroutineServer::class),
            'Router' => create(WeicheRouter::class),
            'Signer' =>create(WeicheSigner::class)
        ];
    }
}
