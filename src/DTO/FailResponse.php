<?php

namespace Weiche\Scheduler\DTO;

/**
 * 便利响应类：执行失败，需要重试
 * Class FailResponse
 * @package Weiche\Scheduler\DTO
 */
class FailResponse extends Response
{
    public function __construct(array $data = [], string $message = '')
    {
        parent::__construct(self::CODE_FAIL, $data, $message);
    }
}
