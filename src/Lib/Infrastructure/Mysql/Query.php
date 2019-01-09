<?php

namespace Scheduler\Infrastructure\MySQL;

class Query
{
    private $transaction;

    /**
     * Query constructor.
     * @param Transaction $transaction 事务管理器
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * 开启事务
     * @param string $model
     * @return bool
     * @throws \Exception
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    public function begin($model = 'write'): bool
    {
        return $this->transaction->begin($model);
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit(): bool
    {
        return $this->transaction->commit();
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->transaction->rollback();
    }

    /**
     * 查询
     * @param string $preSql
     * @param array $params
     * @return array 二维数组列表
     */
    public function query(string $preSql, array $params = []): array
    {
        return $this->transaction->command($preSql, $params);
    }

    /**
     * 命令
     * @param string $preSql
     * @param array $params
     * @return int 影响的行数
     */
    public function execute(string $preSql, array $params = []): int
    {
        return $this->transaction->command($preSql, $params);
    }

    /**
     * 便捷方法：查询列表
     * @param string $table
     * @param array $where
     * @param string $fields
     * @param array $join
     * @return array
     */
    public function find(string $table, $where = [], $fields = '*', $join = []): array
    {

    }

    /**
     * 便捷方法：查询一行记录
     * @param string $table
     * @param array $where
     * @param string $fields
     * @return array
     */
    public function findOne(string $table, $where = [], $fields = '*'): array
    {

    }

    /**
     * 便捷方法：分页查询
     * @param string $table
     * @param array $where
     * @param string $fields
     * @param int $page
     * @param int $pageSize
     * @param array $join
     * @return array
     */
    public function pageList(string $table, $where = [], $fields = '*', $page = 0, $pageSize = 20, $join = []): array
    {

    }

    /**
     * 便捷方法：插入一行数据
     * @param string $table
     * @param array $data 一维数组
     * @param bool $replace true 表示使用 replace into ...
     * @return int 插入的行数
     */
    public function insert(string $table, array $data, bool $replace = false): int
    {

    }

    /**
     * 便捷方法：批量插入
     * @param string $table
     * @param array $data
     * @param bool $replace true 表示使用 replace into ...
     * @return int 插入的行数
     */
    public function multiInsert(string $table, array $data, bool $replace = false): int
    {

    }

    /**
     * 最后插入的记录 id。
     * 注意：批量插入取的是插入的第一行记录的 id
     * @return int
     */
    public function lastInsertId(): int
    {

    }

    /**
     * 便捷方法：更新数据
     * @param string $table
     * @param array $data
     * @param $where
     * @return int 更新行数
     */
    public function update(string $table, array $data, $where): int
    {

    }

    /**
     * 便捷方法：删除数据
     * @param string $table
     * @param $where
     * @return int
     */
    public function delete(string $table, $where): int
    {

    }
}
