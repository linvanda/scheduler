<?php

namespace Weiche\Scheduler\Handler;

use Weiche\Scheduler\DTO\Request;
use Weiche\Scheduler\DTO\Response;

/**
 * 工作流处理程序接口
 *
 * Class BaseHandler
 * @package Weiche\Scheduler\Handler
 */
class BaseHandler
{
    /**
     * 处理程序入口方法
     *
     * @param string $nodeName
     * @param Request $originData
     * @param array $prevResponses
     * @return Response
     */
    public function handler(string $nodeName, Request $originData, array $prevResponses = []): Response
    {
        // 默认使用 $nodeName 作为方法名称

    }
}
