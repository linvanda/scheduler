<?php

namespace Weiche\Scheduler\DTO;

/**
 * 便利响应类：执行成功
 * Class OkResponse
 * @package Weiche\Scheduler\DTO
 */
class OkResponse extends Response
{
    public function __construct(array $data = [], string $message = '')
    {
        parent::__construct(self::CODE_OK, $data, $message);
    }
}