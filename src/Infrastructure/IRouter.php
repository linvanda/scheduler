<?php

namespace Weiche\Scheduler\Infrastructure;

/**
 * 路由接口
 * Interface IRouter
 * @package Weiche\Scheduler\Infrastructure
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
    public function request();

    /**
     * 工作流名称
     * @return string
     */
    public function workflow();
}
