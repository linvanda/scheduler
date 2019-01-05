<?php

namespace Scheduler\Infrastructure\Mysql;

use Swoole\Coroutine as co;
use Scheduler\Utils\Config;

/**
 * 协程版连接池
 * Class CoPool
 * @package Scheduler\Infrastructure\Mysql
 */
class CoPool implements IPool
{
    /**
     * @var co\Channel
     */
    protected $readPool;
    /**
     * @var co\Channel
     */
    protected $writePool;
    protected $connectNum;

    /**
     * CoPool constructor.
     * @throws \Exception
     */
    protected function __construct()
    {
        if (co::getuid() < 0) {
            throw new \Exception("请在协程环境中使用 Mysql 协程连接池");
        }

        $this->readPool = new co\Channel(Config::get('mysql.pool_size', 10));
        $this->writePool = new co\Channel(Config::get('mysql.pool_size'));
        $this->connectNum = 0;
    }

    /**
     * 从连接池中获取 Mysql 连接对象
     * @param string $type
     * @return Connector
     * @throws \Exception
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    public function getConnector($type = 'write'): Connector
    {
        $pool = $this->getPool($type);
        if ($pool->isEmpty() && $this->connectNum < $pool->capacity) {
            // 创建新连接
            $this->connectNum++;
            return  $this->createConnector($type);
        }

        return $pool->pop(10);
    }

    /**
     * 归还连接
     * @param Connector $connector
     * @param string $type
     */
    public function pushConnector(Connector $connector, $type = 'write')
    {
        $pool = $this->getPool($type);

        if (!$pool->isFull()) {
            $pool->push($connector);
        } else {
            $connector->close();
        }
    }

    protected function getPool($type = 'write')
    {
        if (!$type || !in_array($type, ['read', 'write'])) {
            $type = 'write';
        }

        return $type === 'write' ? $this->writePool : $this->readPool;
    }

    /**
     * @param string $type
     * @return CoConnector
     * @throws \Exception
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    protected function createConnector($type = 'write')
    {
        $config = Config::get("mysql");

        if (!$config) {
            throw new \Exception("未找到 MySQL 配置");
        }

        if (!($config = $config[$type])) {
            $config = $config['write'] && is_array($config['write']) ? $config['write'] : $config;
        }

        return new CoConnector(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['database'],
            $config['port'] ?: 3306,
            $config['timeout'] ?: 3,
            $config['charset'] ?: 'utf8',
            true,
            true
        );
    }
}
