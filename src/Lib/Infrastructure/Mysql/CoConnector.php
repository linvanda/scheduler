<?php

namespace Scheduler\Infrastructure\Mysql;

use Swoole\Coroutine\MySQL;

/**
 * 协程版连接器
 * Class Connector
 * @package Scheduler\Infrastructure\Mysql
 */
class CoConnector extends Connector
{
    public function connector()
    {
        return new MySQL();
    }

    /**
     * 建立连接
     * @throw \Exception 连接失败将抛出异常
     * @return bool 连接成功返回 true
     * @throws \Exception
     */
    public function connect(): bool
    {
        $result = $this->mysql->connect(
            [
                'host' => $this->host,
                'user' => $this->user,
                'password' => $this->password,
                'database' => $this->database,
                'port'    => $this->port,
                'timeout' => $this->timeout,
                'charset' => $this->charset,
                'fetch_mode' => $this->fetchMode
            ]
        );

        if (!$result) {
            throw new \Exception("连接数据库失败。错误码：{$this->mysql->errno}，错误：{$this->mysql->error}");
        }

        return true;
    }

    /**
     * 关闭连接
     * @return bool
     */
    public function close(): bool
    {
        $this->mysql->close();
    }
}
