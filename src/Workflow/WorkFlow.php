<?php

namespace Weiche\Scheduler\Workflow;

/**
 * 工作流
 * 未开始；进行中；成功（全部节点（包括条件节点）执行成功）；失败（至少有一个节点宣告最终失败（后面可能存在受其影响未执行的节点））；
 * Class WorkFlow
 * @package Weiche\Scheduler\Workflow
 */
class WorkFlow
{
    // 初始化后尚未开始，等待执行
    const STATUS_INIT = 1;
    // 节点执行中
    const STATUS_DOING = 2;
    // 工作流最终执行成功
    const STATUS_SUC = 3;
    // 工作流最终执行失败
    const STATUS_FAIL = 4;

    // 节点集合
    protected $nodes = [];
    // 当前正在执行的节点
    protected $currentNode;

    public function __construct($name)
    {

    }

    public function run()
    {

    }
}
