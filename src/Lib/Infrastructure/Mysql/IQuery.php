<?php

namespace Scheduler\Infrastructure\Mysql;

/**
 * 执行器
 * Interface IQuery
 * @package Scheduler\Infrastructure\Mysql
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
     * 查询数据并返回结果集数组
     * @param string $preSql
     * @param array $params
     * @return array
     */
    public function query(string $preSql, array $params = []): array;

    /**
     * 查询数据并返回 FlashCollection，主要针对批处理等任务一次返回超大结果集导致内存溢出的，采用 fetch 模式一次只取出一行处理
     * @param string $preSql
     * @param array $params
     * @return FlashCollection
     */
    public function fetch(string $preSql, array $params = []);

    /**
     * 执行 SQL 并返回影响行数（如果 commitTogether 为 true 则返回 null）
     * @param string $preSql
     * @param array $params
     * @return int|null
     */
    public function execute(string $preSql, array $params = []);
}
