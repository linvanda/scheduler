<?php

namespace Scheduler\Infrastructure\MySQL;

/**
 * 执行器
 * 每次调用 query 或 execute 时从 Pool 中获取连接对象，执行完成后释放连接对象
 * Interface IQuery
 * @package Scheduler\Infrastructure\MySQL
 */
interface IQuery
{
    /**
     * 开启事务
     * @param bool $commitTogether 事务内的所有 SQL 是否等到 commit/rollback 时一起提交给 MySQL，如果事务内部不需要使用前面 SQL
     * 的执行结果，则建议设置为 true 以减少通讯次数
     * @return bool
     */
    public function begin($commitTogether = false): bool;

    /**
     * 提交事务
     * @return bool
     */
    public function commit(): bool;

    /**
     * 回滚事务
     * @return bool
     */
    public function rollback(): bool;

    /**
     * 查询数据并返回结果集数组（如果开启了事务且 $commitTogether = true，则 query 不会返回数据结果集）
     * @param string $preSql
     * @param array $params
     * @return array
     */
    public function query(string $preSql, array $params = []): array;

    /**
     * 执行 SQL 并返回影响行数（如果 $commitTogether 为 true 则返回 true|false）
     * @param string $preSql
     * @param array $params
     * @return int|null
     */
    public function execute(string $preSql, array $params = []);
}
