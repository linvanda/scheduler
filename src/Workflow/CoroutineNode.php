<?php

namespace Weiche\Scheduler\Workflow;
use Weiche\Scheduler\Controller\Controller;
use Weiche\Scheduler\DTO\FatalResponse;
use Weiche\Scheduler\DTO\Request;

/**
 * 协程版节点
 * Class CoroutineNode
 * @package Weiche\Scheduler\Workflow
 */
class CoroutineNode extends Node
{
    /**
     * 为了多节点并发执行，协程版节点 run 不能对外抛出异常，需将异常转换成 Response 并更正自己的 status
     * @param Controller $controller
     * @param Request $request
     * @param array $prevResponse
     */
    public function run(Controller $controller, Request $request, array $prevResponse = [])
    {
        try {
            parent::run($controller, $request, $prevResponse);
        } catch (\Exception $e) {
            $this->status = self::STATUS_FAIL;
            $this->response = $this->response ?: new FatalResponse([], "节点执行抛出异常：" . print_r($e, true));
        }
    }
}
