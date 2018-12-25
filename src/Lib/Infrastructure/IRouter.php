<?php

namespace Scheduler\Infrastructure;

use Scheduler\Workflow\WorkFlow;

/**
 * 路由接口
 * Interface IRouter
 * @package Scheduler\Infrastructure
 */
interface IRouter
{
    /**
     * IRouter constructor.
     * @param string|array $request 请求原始参数
     */
    public function __construct($request);

    /**
     * 获取请求对象
     * @return Request
     */
    public function request(): Request;

    /**
     * 工作流名称
     * @return WorkFlow
     */
    public function workflow(): WorkFlow;
}
