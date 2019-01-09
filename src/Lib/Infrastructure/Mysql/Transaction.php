<?php

namespace Scheduler\Infrastructure\MySQL;

/**
 * 事务管理器
 * 注意：事务开启直到提交/回滚的过程中会一直占用某个 IConnector 实例，如果有很多长事务，则会很快耗完连接池资源
 * Class Transaction
 * @package Scheduler\Infrastructure\MySQL
 */
class Transaction
{
    private $pool;
    private $isRunning;
    private $commandPool = [];
    private $model;// 读模式还是写模式 write/read
    private $connector;

    public function __construct(IPool $pool)
    {
        $this->pool = $pool;
    }

    public function __destruct()
    {
        if ($this->isRunning) {
            $this->commit();
        }
    }

    /**
     * @param string $model write/read 读模式还是写模式，针对读写分离
     * @param bool $isImplicit 是否隐式事务，隐式事务不会向 MySQL 提交 begin 请求
     * @return bool
     * @throws \Exception
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    public function begin(string $model = 'write', bool $isImplicit = false): bool
    {
        // 如果事务已经开启了，则直接返回
        if ($this->isRunning) {
            return true;
        }

        $this->isRunning = true;
        $this->model = $model;

        // 开启事务时需 handle 一个 Connector，直到事务提交
        if (!($this->connector = $this->getConnector())) {
            return false;
        }

        return $isImplicit || $this->command('begin');
    }

    /**
     * 发送指令
     * @param string $preSql
     * @param array $params
     * @return bool|mixed
     * @throws
     */
    public function command(string $preSql, array $params = [])
    {
        // 如果当前不在事务中，则开启一个事务（隐式事务）
        $isImplicit = !$this->isRunning;

        if ($isImplicit && !$this->begin($this->model([[$preSql, $params]]), $isImplicit)) {
            return false;
        }

        $this->commandPool[] = [$preSql, $params];
        $result = $this->exec();

        if ($isImplicit && !$this->commit($isImplicit)) {
            return false;
        }

        return $result;
    }

    /**
     * 提交事务
     * @param bool $isImplicit 是否隐式事务，隐式事务不会向 MySQL 提交 commit
     * @return bool
     */
    public function commit(bool $isImplicit = false): bool
    {
        if (!$this->isRunning) {
            return true;
        }

        $result = true;
        if (!$isImplicit) {
            $result = $this->command('commit');

            if ($result === false) {
                // 执行失败，试图回滚
                $this->rollback();
            }
        }

        $this->releaseTransResource();

        return $result;
    }

    public function rollback(): bool
    {
        if (!$this->isRunning) {
            return true;
        }

        // 回滚前指令池中的指令一律清空
        $this->commandPool = [];
        $result = $this->command('rollback');

        $this->releaseTransResource();

        return $result;
    }

    /**
     * 获取或设置当前事务执行模式
     * @param string|array $model string: read/write; array: 格式同 commandPool
     * @return string
     */
    public function model($model = null): string
    {
        // 事务处于开启状态时不允许切换运行模式
        if ($this->isRunning) {
            return $this->model;
        }

        if ($model === null || is_array($model)) {
            // 根据指令池内容计算运行模式(只要有一条写指令则是 write 模式)
            $mdl = 'read';
            foreach (($model ?: $this->commandPool) as $command) {
                $sqls = array_map(
                    function ($item) {
                        return trim($item);
                    },
                    array_filter(explode(';', $command[0]))
                );

                foreach ($sqls as $sql) {
                    if (preg_match('/^(update|replace|delete|insert|drop|grant|truncate|alter|create)\s/i', $sql)) {
                        $mdl = 'write';
                        goto rtn;
                    }
                }
            }

            rtn:
            return $mdl;
        }

        $this->model = $model === 'read' ? 'read' : 'write';

        return $this->model;
    }

    /**
     * 释放事务资源
     */
    private function releaseTransResource()
    {
        $this->isRunning = false;
        $this->model = null;
        $this->pool->pushConnector($this->connector);
        $this->connector = null;
    }

    /**
     * 执行指令池中的指令
     * @return mixed
     * @throws
     */
    private function exec()
    {
        // 执行指令的前提是在事务开启模式下且指令池中有指令
        if (!$this->commandPool || !$this->isRunning) {
            return true;
        }

        $result = $this->getConnector()->query(...$this->prepareSql($this->commandPool));

        // 执行完毕后清空指令池
        $this->commandPool = [];

        return $result;
    }

    /**
     * 根据指令池组装 SQL
     * 注意：该方法支持批量 SQL 执行（多个 SQL 用 ; 隔开），但 swoole 的 MySQL 库目前不支持该特性，因而该类目前并没有使用该特性
     * @param array $commandPool
     * @return array
     */
    private function prepareSql(array $commandPool): array
    {
        $sql = '';
        $params = [];
        foreach ($commandPool as $command) {
            if (!$command[0]) {
                continue;
            }

            if ($command[1]) {
                // 增加额外后缀防止重复
                $i = 0;
                foreach ($command[1] as $k => $p) {
                    $ik = $k . "_$i";
                    $params[$ik] = $p;
                    $command[0] = preg_replace("/:{$k}(?=[^a-zA-Z0-9_-]|$)/", ":$ik", $command[0]);
                    $i++;
                }
            }

            $sql .= $command[0] . ';';
        }

        return [$sql, $params];
    }

    /**
     * @return IConnector
     * @throws \Exception
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    private function getConnector()
    {
        if ($this->connector) {
            return $this->connector;
        }

        return $this->pool->getConnector($this->model());
    }
}
