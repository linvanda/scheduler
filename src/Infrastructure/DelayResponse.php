<?php

namespace Weiche\Scheduler\Infrastructure;

/**
 * 便利响应类：延迟执行
 * Class DelayResponse
 * @package Weiche\Scheduler\Infrastructure
 */
class DelayResponse extends Response
{
    public function __construct(array $data = [], string $message = '')
    {
        parent::__construct(self::CODE_DELAY, $data, $message);
    }
}
