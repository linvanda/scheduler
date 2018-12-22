<?php

namespace Scheduler\Infrastructure\Response;

/**
 * 便利响应类：发生致命错误，无法通过重试解决，外界无需重试
 * Class FatalResponse
 * @package Scheduler\Infrastructure\Response
 */
class FatalResponse extends Response
{
    public function __construct(array $data = [], string $message = '', string $desc = '')
    {
        parent::__construct(self::CODE_FATAL, $data, $message, $desc);
    }
}
