<?php

namespace Scheduler\Infrastructure\Response;

/**
 * 便利响应类：执行成功
 * Class OkResponse
 * @package Scheduler\Infrastructure\Response
 */
class OkResponse extends Response
{
    public function __construct(array $data = [], string $message = '')
    {
        parent::__construct(self::CODE_OK, $data, $message);
    }
}
