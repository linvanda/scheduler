<?php

namespace Weiche\Scheduler\Workflow;

use Swoole\Coroutine as co;

/**
 * 协程版工作流
 * Class CoroutineWorkFlow
 * @package Weiche\Scheduler\Workflow
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
            $node->run($this->controller, $this->request, $this->getPrevNodeResponse($node));
        });
    }
}
