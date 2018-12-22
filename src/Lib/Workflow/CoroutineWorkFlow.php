<?php

namespace Scheduler\Workflow;

use Swoole\Coroutine as co;

/**
 * 协程版工作流
 * Class CoroutineWorkFlow
 * @package Scheduler\Workflow
 */
class CoroutineWorkFlow extends WorkFlow
{
    /**
     * 在独立的协程中执行自节点
     * @param Node $node
     */
    protected function runNode(Node $node)
    {
        co::create(function () use ($node) {
            // 需要捕获里层抛出的异常，否则协程外面是捕获不到的，直接导致该进程退出
            try {
                $node->run($this->controller, $this->request, $this->getPrevNodeResponse($node));
            } catch (\Exception $e) {
                // 将节点设置为执行失败
                $node->fail($e->getMessage(), $e->getTraceAsString());
            }
        });
    }
}
