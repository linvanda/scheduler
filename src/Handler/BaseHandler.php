<?php

namespace Weiche\Scheduler\Handler;

use Weiche\Scheduler\Infrastructure\DTO\OriginData;

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
     * @param OriginData $originData
     * @param array $prevResponses
     */
    public function handler(string $nodeName, OriginData $originData, array $prevResponses = [])
    {
        // 默认使用 $nodeName 作为方法名称

    }
}
