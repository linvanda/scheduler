<?php

namespace Scheduler\Infrastructure\MySQL;

/**
 * 事务管理器
 * Class Transaction
 * @package Scheduler\Infrastructure\MySQL
 */
class Transaction
{
    private $connector;

    public function __construct(IConnector $connector)
    {
        $this->connector = $connector;
    }
}
