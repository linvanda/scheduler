<?php

namespace Scheduler\Infrastructure\Response;

/**
 * 特殊的 Response，代表 None，表示节点没有执行完成，没有任何返回信息
 * Class NoneResponse
 * @package Scheduler\Infrastructure\Response
 */
class NoneResponse extends Response
{
    public function __construct()
    {
        parent::__construct(self::CODE_NONE, [], "节点未完成");
    }
}
