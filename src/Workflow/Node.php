<?php

namespace Weiche\Scheduler\Workflow;

use Weiche\Scheduler\Controller\Controller;
use Weiche\Scheduler\DTO\Request;
use Weiche\Scheduler\Exception\InvalidResponseException;

/**
 * 工作流节点基类
 * Interface Node
 * @package Weiche\Scheduler\Workflow
 */
class Node
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

    protected $name;
    // 处理器的 action 名称
    protected $action;
    // 节点当前状态
    protected $status;
    // 当 status 是延迟执行时，延迟的秒数
    protected $delay;
    // 当需要延迟执行时，从什么时候开始延迟
    protected $delayStartAt;
    // 节点重试次数。延迟执行不算重试
    protected $retryNum = 0;
    // 节点重试时，下次重试的时间
    protected $retryStartAt;
    // 节点执行后返回值
    protected $response;
    // 前置条件
    protected $conditions = [];

    public function __construct(string $name, array $nodeConfig, array $workFlowCfg)
    {
        $this->name = $name;
        $this->init($nodeConfig, $workFlowCfg);
    }

    /**
     * 执行节点
     * @param Controller $controller
     * @param Request $request
     * @param array $prevResponse
     * @return mixed
     * @throws \Weiche\Scheduler\Exception\InvalidCallException
     * @throws InvalidResponseException
     */
    public function run(Controller $controller, Request $request, array $prevResponse = [])
    {
        $this->status = self::STATUS_DOING;

        // 启动控制器
        $this->response = $controller->handler($this->action, $request, $prevResponse);

        // 根据 response 设置节点状态
        switch (intval(substr($this->response->getCode(), 0, 1))) {
            case 2:
                // ok
                $this->status = self::STATUS_SUC;
                break;
            case 3:
                // delay
                $this->status = self::STATUS_DELAY;
                $this->delayStartAt = time();
                break;
            case 4:
                // fail
                $this->status = self::STATUS_RETRY;
                break;
            case 5:
                // fatal
                $this->status = self::STATUS_FAIL;
                break;
            default:
                throw new InvalidResponseException("非法的响应结果");
                break;
        }
    }

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

    protected function init(array $nodeCfg, array $workFlowCfg)
    {
        $this->action = $nodeCfg['action'] ?: $this->name;

        if ($nodeCfg['conditions']) {
            $this->conditions = $nodeCfg['conditions'];
        }

        // 延迟执行的时间间隔
        if ($nodeCfg['delay']) {
            $this->delay = $nodeCfg['delay'];
        } else {
            $this->delay = $workFlowCfg['delay'] ?: 5;
        }
    }
}
