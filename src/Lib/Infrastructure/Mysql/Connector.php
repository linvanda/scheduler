<?php

namespace Scheduler\Infrastructure\Mysql;

use Scheduler\Utils\Config;

/**
 * 连接对象
 * class Connector
 * @package Scheduler\Infrastructure\Mysql
 */
abstract class Connector
{
    protected $mysql;
    protected $host;
    protected $user;
    protected $password;
    protected $database;
    protected $port;
    protected $timeout;
    protected $charset;
    protected $fetchMode;

    /**
     * Connector constructor.
     * @param $host
     * @param $user
     * @param $password
     * @param $database
     * @param int $port
     * @param int $timeout
     * @param string $charset
     * @param bool $fetchMode 是否开启 fetch 模式
     * @param bool $autoConnect 创建对象时是否自动连接
     * @throws \Exception
     */
    public function __construct
    (
        $host,
        $user,
        $password,
        $database,
        int $port = 3306,
        int $timeout = 3,
        string $charset = 'utf8',
        bool $fetchMode = false,
        bool $autoConnect = true
    ) {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->charset = $charset;
        $this->fetchMode = $fetchMode;

        $this->mysql = $this->connector();

        if ($autoConnect) {
            $this->connect();
        }
    }

    /**
     * 工厂方法，创建 Connector 对象
     * @param string $type
     * @return Connector
     * @throws \Exception
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    public static function create($type = 'write')
    {
        $config = Config::get("mysql");

        if (!$config) {
            throw new \Exception("未找到 MySQL 配置");
        }

        if (!($config = $config[$type])) {
            $config = $config['write'] && is_array($config['write']) ? $config['write'] : $config;
        }

        return new static(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['database'],
            $config['port'] ?: 3306,
            $config['timeout'] ?: 3,
            $config['charset'] ?: 'utf8',
            $config['fetch_mode'] ?? false,
            true
        );
    }

    /**
     * 建立连接
     * @throw \Exception 连接失败将抛出异常
     * @return bool 连接成功返回 true
     */
    abstract public function connect(): bool;

    /**
     * 关闭连接
     * @return bool
     */
    abstract public function close(): bool;

    /**
     * 返回连接对象
     * @return mixed
     */
    abstract protected function connector();
}
