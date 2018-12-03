<?php

namespace Weiche\Scheduler\Workflow;

/**
 * 工作流节点接口
 * Interface Node
 * @package Weiche\Scheduler\Workflow
 */
abstract class Node
{
    // 未执行
    const STATUS_INIT = 1;
    // 执行中
    const STATUS_DOING = 2;
    // 延迟执行
    const STATUS_DELAY = 3;
    // 失败待重试
    const STATUS_RETRY = 4;
    // 失败
    const STATUS_FAIL = 5;
    // 成功
    const STATUS_SUC = 6;

    // 节点当前状态
    protected $status;
    // 节点执行后返回值
    protected $response;

    /**
     * 执行节点
     * @return mixed
     */
    abstract public function run();

    /**
     * 节点的执行结果，如果未执行，则为 null
     * @return \Weiche\Scheduler\DTO\Response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * 节点状态
     * @return int
     */
    public function status()
    {
        return $this->status;
    }
}
