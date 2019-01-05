<?php

namespace Scheduler\Infrastructure\Mysql;

/**
 * 事务管理器
 * Class Transaction
 * @package Scheduler\Infrastructure\Mysql
 */
class Transaction
{
    private $connector;

    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }
}
