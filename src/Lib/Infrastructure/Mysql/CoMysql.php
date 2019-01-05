<?php

namespace Scheduler\Infrastructure\Mysql;

use Swoole\Coroutine\MySQL;

class CoMysql implements IDriver
{
    /**
     * @var MySQL
     */
    private $mysql;
    private $statement;
    private $isFetchMode;
    private $collection;
    private $fetchIndex = 0;

    public function __construct()
    {
        $this->mysql = new MySQL();
    }

    public function connect(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        int $timeout = 3,
        string $charset = 'utf8',
        bool $fetchMode = false
    ): bool {
        $this->isFetchMode = $fetchMode;

        $this->statement = null;
        $this->collection = null;
        $this->fetchIndex = 0;

        return $this->mysql->connect(
            [
                'host' => $host,
                'user' => $user,
                'password' => $password,
                'database' => $database,
                'port'    => $port,
                'timeout' => $timeout,
                'charset' => $charset,
                'strict_type' => false,
                'fetch_mode' => $fetchMode,
            ]
        );
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        $this->mysql->close();
    }

    public function query(string $sql, array $params, $prepare = true)
    {
        return $this->mysql->query($sql);
    }

    /**
     * fetch 一行数据
     * @return array
     */
    public function fetch(): array
    {
        if ($this->isFetchMode) {
            return $this->statement->fetch();
        }

        // 模拟 fetch 模式
        if ($this->collection && $this->fetchIndex < count($this->collection)) {
            return $this->collection[$this->fetchIndex++];
        }

        return [];
    }

    /**
     * fetch 整个结果集
     * @return array
     */
    public function fetchAll(): array
    {
        if ($this->isFetchMode) {
            return $this->statement->fetchAll();
        }

        return $this->collection ?? [];
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
     * @return mixed
     */
    public function insertId()
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

    /**
     * 连接错误码
     * @return int
     */
    public function lastConnectErrorNo(): int
    {
        return $this->mysql->connect_errno;
    }


    /**
     * prepare
     * @param string $sql
     * @return bool
     */
    private function prepare(string $sql): bool
    {
        $this->statement = null;

        if (!($statement = $this->mysql->prepare($sql))) {
            return false;
        }

        $this->statement = $statement;

        return true;
    }

    /**
     * prepare 后 execute
     * @param array $params
     * @return bool
     */
    private function execute(array $params): bool
    {
        $this->collection = null;
        $this->fetchIndex = 0;

        if (!$this->statement) {
            return false;
        }

        $result = $this->statement->execute($params);

        if ($result === false) {
            return false;
        }

        if ($this->isFetchMode) {
            return true;
        }

        // 非 fetch 模式记录结果，供后面模拟 fetch 模式用
        $this->collection = $result;

        return true;
    }
}
