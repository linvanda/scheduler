<?php

namespace Weiche\Scheduler\Workflow;

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

    }

    /**
     * 获取可以执行的节点
     */
    protected function getReadiedNodes()
    {

    }
}
