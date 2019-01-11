<?php

namespace Scheduler\Fundation\MySQL;

/**
 * 连接池
 * Interface IPool
 * @package Scheduler\Fundation\MySQL
 */
 interface IPool
 {
     /**
      * 获取 IPool 单例
      * @param int $size 连接池大小
      * @param int $maxSleepTime 池中对象最多空闲时间，超过此时间将被回收
      * @param int $maxExecCount 连接对象最多执行 SQL 次数，超过此次数会重新连接
      * @return IPool
      */
     public static function instance(int $size, int $maxSleepTime = 600, int $maxExecCount = 1000): IPool;
     /**
      * 从连接池中获取 MySQL 连接对象
      * @param string $type
      * @return IConnector
      * @throws \Exception
      * @throws \Scheduler\Exception\FileNotFoundException
      */
    public function getConnector(string $type = 'write'): IConnector;
     /**
      * 归还连接
      * @param IConnector $connector
      * @return bool
      */
     public function pushConnector(IConnector $connector): bool;
 }
