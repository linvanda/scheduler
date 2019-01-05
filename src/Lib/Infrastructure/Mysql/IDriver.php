<?php

namespace Scheduler\Infrastructure\Mysql;

/**
 * 驱动接口
 * Interface IDriver
 * @package Scheduler\Infrastructure\Mysql
 */
interface IDriver
{
    /**
     * 连接数据库
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     * @param int $port
     * @param int $timeout
     * @param string $charset
     * @param bool $fetchMode
     * @return bool 成功 true，失败 false
     */
    public function connect(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        int $timeout = 3,
        string $charset = 'utf8',
        bool $fetchMode = false
    ): bool;

    /**
     * 关闭连接
     */
    public function close();

    /**
     * 执行 SQL
     * @param string $sql 预处理 SQL，占位符用 :name 这种格式
     * @param array $params 参数
     * @param bool $prepare 是否使用 prepare 模式
     * @return array|bool 如果是查询语句，则返回结果集，否则（包括失败）返回 bool
     */
    public function query(string $sql, array $params, bool $prepare = true);

    /**
     * fetch 一行数据
     * @return array
     */
    public function fetch(): array;

    /**
     * fetch 整个结果集
     * @return array
     */
    public function fetchAll(): array;

    /**
     * SQL 执行影响的行数，针对命令型 SQL
     * @return int
     */
    public function affectedRows(): int;

    /**
     * 最后插入的记录 id
     * @return mixed
     */
    public function insertId();

    /**
     * 最后的错误码
     * @return int
     */
    public function lastErrorNo(): int;

    /**
     * 连接错误码
     * @return int
     */
    public function lastConnectErrorNo(): int;
}
