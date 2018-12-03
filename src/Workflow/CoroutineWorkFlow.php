<?php

namespace Weiche\Scheduler\Workflow;

/**
 * 协程版工作流
 * Class CoroutineWorkFlow
 * @package Weiche\Scheduler\Workflow
 */
class CoroutineWorkFlow extends WorkFlow
{


    // 节点集合
    protected $nodes = [];
    // 当前正在执行的节点
    protected $currentNode;

    public function __construct(string $name)
    {

    }


}
