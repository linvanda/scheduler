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
     * @throws \Weiche\Scheduler\Exception\InvalidConfigException
     */
    protected function runNodes()
    {
        foreach ($this->nodes as $node) {
            if ($this->canNodeExec($node)) {
                // 在子协程中执行
                co::create(function () use ($node) {
                    $node->run($this->controller, $this->request, $this->getPrevNodeResponse($node));
                });
            }
        }
    }

    protected function createNode($name, $nodeCfg)
    {
        return new CoroutineNode($name, $nodeCfg);
    }
}
