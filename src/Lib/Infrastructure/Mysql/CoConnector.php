<?php

namespace Scheduler\Infrastructure\Mysql;

use Swoole\Coroutine\MySQL;

/**
 * 协程版连接器
 * Class Connector
 * @package Scheduler\Infrastructure\Mysql
 */
class CoConnector extends Connector
{
    public function connector()
    {
        return new MySQL();
    }
}
