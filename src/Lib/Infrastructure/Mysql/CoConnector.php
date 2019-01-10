<?php

namespace Scheduler\Infrastructure\MySQL;

use Swoole\Coroutine\MySQL;

/**
 * 协程版连接器
 * Class CoConnector
 * @package Scheduler\Infrastructure\MySQL
 */
class CoConnector implements IConnector
{
    /**
     * @var MySQL
     */
    private $mysql;
    private $config;

    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        int $timeout = 3,
        string $charset = 'utf8'
    ) {
        $this->config = [
            'host' => $host,
            'user' => $user,
            'password' => $password,
            'database' => $database,
            'port'    => $port,
            'timeout' => $timeout,
            'charset' => $charset,
            'strict_type' => false,
            'fetch_mode' => false,
        ];

        $this->mysql = new MySQL();

        $this->connect();
    }

    public function connect(): bool
    {
        if ($this->mysql->connected) {
            return true;
        }

        return $this->mysql->connect($this->config);
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        $this->mysql->close();
    }

    /**
     * 执行 SQL 语句
     * 对于有 $params的 SQL，强制走 prepare
     * @param string $sql
     * @param array $params
     * @param int $timeout
     * @return mixed
     * @throws \Exception
     */
    public function query(string $sql, array $params, int $timeout = 120)
    {
        $prepare = $params ? true : false;

        if ($prepare) {
            $statement = $this->mysql->prepare($sql, $timeout);

            if ($statement === false && $this->tryReconnectForQueryFail()) {
                // 失败，尝试重新连接数据库
                $statement = $this->mysql->prepare($sql, $timeout);
            }

            if ($statement === false) {
                return false;
            }

            // execute
            $result = $statement->execute($params, $timeout);
        } else {
            $result = $this->mysql->query($sql, $timeout);

            if ($result === false && $this->tryReconnectForQueryFail()) {
                $result = $this->mysql->query($sql, $timeout);
            }
        }

        return $result;
    }

    /**
     * SQL 执行影响的行数，针对命令型 SQL
     * @return int
     */
    public function affectedRows(): int
    {
        return $this->mysql->affected_rows;
    }

    /**
     * 最后插入的记录 id
     * @return int
     */
    public function insertId(): int
    {
        return $this->mysql->insert_id;
    }

    /**
     * 最后的错误码
     * @return int
     */
    public function lastErrorNo(): int
    {
        return $this->mysql->errno;
    }

    public function lastError(): string
    {
        return $this->mysql->error;
    }

    /**
     * 失败重连
     */
    private function tryReconnectForQueryFail()
    {
        if ($this->mysql->connected || !in_array($this->mysql->errno, [2006, 2013])) {
            return false;
        }

        // 尝试重新连接
        return $this->mysql->connect($this->config);
    }
}
