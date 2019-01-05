<?php

namespace Scheduler\Infrastructure\Mysql;

/**
 * 连接对象
 * class Connector
 * @package Scheduler\Infrastructure\Mysql
 */
abstract class Connector
{
    /**
     * @var IDriver
     */
    protected $driver;

    protected $host;

    protected $user;

    protected $password;

    protected $database;

    protected $port;

    protected $timeout;

    protected $charset;

    protected $fetchMode;

    protected $lastErrNo;

    /**
     * Connector constructor.
     * @param IDriver $driver
     * @param $host
     * @param $user
     * @param $password
     * @param $database
     * @param int $port
     * @param int $timeout
     * @param string $charset
     * @param bool $fetchMode
     * @param bool $autoConnect
     * @throws \Exception
     */
    public function __construct(
        IDriver $driver,
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        int $timeout = 3,
        string $charset = 'utf8',
        bool $fetchMode = true,
        bool $autoConnect = false
    ) {
        $this->driver = $driver;
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->charset = $charset;
        $this->fetchMode = $fetchMode;

        if ($autoConnect) {
            $this->connect();
        }
    }

    /**
     * 建立连接
     * @throw \Exception 连接失败将抛出异常
     * @return bool 连接成功返回 true
     * @throws \Exception
     */
    public function connect(): bool
    {
        $result = $this->driver->connect(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port,
            $this->timeout,
            $this->charset,
            $this->fetchMode
        );

        if (!$result) {
            throw new \Exception("连接数据库失败。错误码：{$this->driver->lastConnectErrorNo()}");
        }

        return true;
    }

    /**
     * 关闭连接
     * @return bool
     */
    public function close(): bool
    {
        return $this->driver->close();
    }

    /**
     * 执行 SQL 语句，返回 array 或 true，如果失败返回 false
     * @param string $sql
     * @return array|bool
     * @throws \Exception
     */
    public function query(string $sql)
    {
        $result = $this->driver->query($sql);

        if ($result === false) {
            if (in_array($this->driver->lastConnectErrorNo(), [2006, 2013])) {
                // 断线重连
                $this->connect();

                $result = $this->driver->query($sql);
            }
        }

        if ($result === false) {
            $this->lastErrNo = $this->driver->lastErrorNo();
            return false;
        }

        return $result;
    }

    /**
     * 返回连接对象
     * @return mixed
     */
    abstract protected function connector();
}
