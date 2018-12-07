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
     * 执行节点任务
     * 此处采用贪婪模式，即一次执行尽可能多的节点，每个节点在子协程中单独执行，保证并发性
     * @return mixed
     */
    protected function runNodes()
    {
        foreach ($this->nodes as $node) {
            if ($this->isNodeReady($node)) {
                // 在子协程中执行
                co::create(function () use ($node) {
                    $node->run($this->controller, $this->request, $this->getPrevNodeResponse($node));
                });
            }
        }
    }

    /**
     * 获取可以执行的节点
     * @param Node $node
     * @return bool
     */
    protected function isNodeReady(Node $node)
    {
        if (in_array($node->status(), [Node::STATUS_FAIL, Node::STATUS_SUC, Node::STATUS_DOING])) {
            return false;
        }

        return $node->isAwake();
    }

    /**
     * 获取前置节点的响应数据
     * @param Node $node
     * @return array
     */
    protected function getPrevNodeResponse(Node $node)
    {
        return [];
    }
}
