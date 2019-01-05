<?php

namespace Scheduler\Infrastructure\Mysql;

/**
 * 连接池
 * Interface IPool
 * @package Scheduler\Infrastructure\Mysql
 */
 interface IPool
 {
     /**
      * 从连接池中获取 Mysql 连接对象
      * @param string $type
      * @return mixed|Connector
      * @throws \Exception
      * @throws \Scheduler\Exception\FileNotFoundException
      */
    public function getConnector($type = 'write'): Connector;
     /**
      * 归还连接
      * @param Connector $connector
      * @param string $type
      */
     public function pushConnector(Connector $connector, $type = 'write');
 }
