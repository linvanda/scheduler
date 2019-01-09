<?php

namespace Scheduler\Infrastructure\MySQL;

/**
 * 连接器接口
 * Interface IConnector
 * @package Scheduler\Infrastructure\MySQL
 */
interface IConnector
{
    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     * @param int $port
     * @param int $timeout
     * @param string $charset
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        int $timeout = 3,
        string $charset = 'utf8'
    );

    /**
     * 连接数据库
     * @return bool 成功 true，失败 false
     */
    public function connect(): bool;

    /**
     * 关闭连接
     */
    public function close();

    /**
     * 执行 SQL
     * $sql 格式：select * from t_name where uid=:uid
     * @param string $sql 预处理 SQL，占位符用 :name 这种格式。['uid' => $uid]
     * @param array $params 参数
     * @param int $timeout 查询超时时间，默认 2 分钟
     * @return mixed 失败返回 false；成功：查询返回数组，否则返回 true
     */
    public function query(string $sql, array $params, int $timeout = 120);

    /**
     * SQL 执行影响的行数，针对命令型 SQL
     * @return int
     */
    public function affectedRows(): int;

    /**
     * 最后插入的记录 id
     * @return int
     */
    public function insertId(): int;

    /**
     * 最后的错误码
     * @return int
     */
    public function lastErrorNo(): int;

    /**
     * 错误信息
     * @return string
     */
    public function lastError(): string;
}
