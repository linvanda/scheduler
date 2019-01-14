<?php

namespace Scheduler\Fundation\MySQL;

use Swoole\Coroutine as co;
use Scheduler\Utils\Config;

/**
 * 协程版连接池
 * 注意：一旦连接池被销毁，连接池持有和分配出去的连接对象都会被关闭（哪怕该连接对象还在被外面使用）
 * Class CoPool
 * @package Scheduler\Fundation\MySQL
 */
class CoPool implements IPool
{
    protected static $instance;

    /** @var co\Channel */
    protected $readPool;
    /** @var co\Channel */
    protected $writePool;
    // 记录每个连接的相关信息
    protected $connectsInfo = [];
    protected $connectNum;
    protected $maxSleepTime;
    protected $maxExecCount;

    /**
     * CoPool constructor.
     * @param int $size
     * @param int $maxSleepTime
     * @param int $maxExecCount
     * @throws \Exception
     */
    protected function __construct(int $size, int $maxSleepTime = 600, int $maxExecCount = 1000)
    {
        if (co::getuid() < 0) {
            throw new \Exception("请在协程环境中使用 MySQL 协程连接池");
        }

        $this->readPool = new co\Channel($size);
        $this->writePool = new co\Channel($size);
        $this->maxSleepTime = $maxSleepTime;
        $this->maxExecCount = $maxExecCount;
        $this->connectNum = 0;
    }

    public function __destruct()
    {
        // 销毁所有的连接对象

    }

    public static function instance(int $size, int $maxSleepTime = 600, int $maxExecCount = 1000): IPool
    {
        if (!static::$instance) {
            static::$instance = new static($size, $maxSleepTime, $maxExecCount);
        }

        return static::$instance;
    }

    /**
     * 从连接池中获取 MySQL 连接对象
     * @param string $type
     * @return IConnector
     * @throws \Exception
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    public function getConnector(string $type = 'write'): IConnector
    {
        $pool = $this->getPool($type);
        if ($pool->isEmpty() && $this->connectNum < $pool->capacity) {
            // 创建新连接
            $conn = $this->createConnector($type);

            if ($conn) {
                goto done;
            }

            return $conn;
        }

        $conn = $pool->pop(10);

        done:
        $connectInfo = $this->connectInfo($conn);
        $connectInfo->popTime = time();
        $connectInfo->status = ConnectorInfo::STATUS_BUSY;

        return $conn;
    }

    /**
     * 归还连接
     * @param IConnector $connector
     * @return bool
     */
    public function pushConnector(IConnector $connector): bool
    {
        $connInfo = $this->connectInfo($connector);
        $pool = $this->getPool($connInfo->type);

        // 先改变状态
        $connInfo && $connInfo->status = ConnectorInfo::STATUS_IDLE;

        if ($pool->isFull() || !$this->isHealthy($connInfo)) {
            return $this->closeConnector($connector);
        }

        $connInfo->pushTime = time();
        return $pool->push($connector);
    }

    protected function closeConnector(IConnector $connector)
    {
        $connector->close();
        $this->connectNum--;
        unset($this->connectsInfo[$this->getObjectId($connector)]);

        return true;
    }

    /**
     * @param string $type
     * @return co\Channel
     */
    protected function getPool($type = 'write'): co\Channel
    {
        if (!$type || !in_array($type, ['read', 'write'])) {
            $type = 'write';
        }

        return $type === 'write' ? $this->writePool : $this->readPool;
    }

    /**
     * @param string $type
     * @return IConnector
     * @throws \Exception
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    protected function createConnector($type = 'write'): IConnector
    {
        $config = Config::get("mysql");

        if (!$config) {
            throw new \Exception("未找到 MySQL 配置");
        }

        if (!($config = $config[$type])) {
            $config = $config['write'] && is_array($config['write']) ? $config['write'] : $config;
        }

        $conn = new CoConnector(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['database'],
            $config['port'] ?: 3306,
            $config['timeout'] ?: 3,
            $config['charset'] ?: 'utf8'
        );

        if ($conn) {
            $this->connectNum++;
            $this->connectsInfo[$this->getObjectId($conn)] = new ConnectorInfo($conn, $type);
        }

        return $conn;
    }

    /**
     * 检查连接对象的健康情况，以下情况视为不健康：
     * 1. SQL 执行次数超过阈值；
     * 2. 连接对象距最后使用时间超过阈值；
     * 3. 连接对象不是连接池创建的
     * @param ConnectorInfo $connectorInfo
     * @return bool
     */
    protected function isHealthy(ConnectorInfo $connectorInfo): bool
    {
        if (!$connectorInfo) {
            return false;
        }

        // 如果连接处于忙态（一般是还处于事务未提交状态），则一律返回 ok
        if ($connectorInfo->status === ConnectorInfo::STATUS_BUSY) {
            return true;
        }

        if (
            $connectorInfo->execCount() >= $this->maxExecCount ||
            time() - $connectorInfo->lastExecTime() >= $this->maxSleepTime
        ) {
            return false;
        }

        return true;
    }

    protected function connectInfo(IConnector $connector): ConnectorInfo
    {
        return $this->connectsInfo[$this->getObjectId($connector)];
    }

    protected function getObjectId($object): string
    {
        return spl_object_hash($object);
    }
}
