<?php

namespace Weiche\Scheduler\Workflow;

use Weiche\Scheduler\Controller\Controller;
use Weiche\Scheduler\DTO\Request;
use Weiche\Scheduler\DTO\Response;
use Weiche\Scheduler\Exception\HandlerException;
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
    protected $delayFrom;
    // 延迟到什么时候
    protected $delayTo;
    // 已延迟次数
    protected $delayedNum = 0;
    // 节点最大重试次数
    protected $maxRetryNum;
    // 最大延迟次数
    protected $maxDelayNum;
    // 节点已重试次数。第一次执行和延迟执行不算重试
    protected $retriedNum = 0;
    // 节点重试时，下次重试的时间
    protected $retryAt;
    /**
     * 节点执行后返回值
     * @var Response
     */
    protected $response;
    // 前置条件
    protected $conditions = [];
    // 重试时间间隔和次数的关系，单位 s
    protected $retryInterval = [1 => 5, 2 => 15, 3 => 30, 4 => 180, 5 => 600, 6 => 1800, 7 => 3600, 8 => 10800, 9 => 18000, 10 => 36000];

    public function __construct(string $name, array $nodeConfig)
    {
        $this->name = $name;
        $this->status = self::STATUS_INIT;
        $this->init($nodeConfig);
    }

    /**
     * 执行节点
     * @param Controller $controller
     * @param Request $request
     * @param array $prevResponse
     * @return mixed
     * @throws InvalidResponseException
     * @throws HandlerException
     */
    public function run(Controller $controller, Request $request, array $prevResponse = [])
    {
        $this->pre();

        try {
            // 启动控制器
            $this->response = $controller->handler($this->action, $request, $prevResponse);
        } catch (\Exception $e) {
            throw new HandlerException($e->getMessage(), $e->getCode());
        } finally {
            $this->post();
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

    /**
     * 节点是否 awake 态（被 delay 的或 retry 的且未到时间则处于 sleep 态）
     * @return bool
     */
    public function isAwake()
    {
        if (!in_array($this->status, [self::STATUS_DELAY, self::STATUS_RETRY])) {
            return true;
        }

        $now = time();

        if (
            $this->status === self::STATUS_DELAY && $this->delayTo >= $now &&
            $this->status === self::STATUS_RETRY && $this->retryAt >= $now
        ) {
            return true;
        }

        return false;
    }

    /**
     * 控制器执行前
     */
    protected function pre()
    {
        // 如果是失败重试或延迟执行，则将相关次数加1
        if ($this->status === self::STATUS_RETRY) {
            $this->retriedNum++;
        } elseif ($this->status === self::STATUS_DELAY) {
            $this->delayedNum++;
        }

        $this->status = self::STATUS_DOING;
    }

    /**
     * 控制器执行完毕后
     * @throws InvalidResponseException
     */
    protected function post()
    {
        // 根据 response 更新节点状态
        switch (intval(substr($this->response->getCode(), 0, 1))) {
            case 2:
                // 成功
                $this->status = self::STATUS_SUC;
                break;
            case 3:
                // 延迟执行
                if ($this->delayedNum >= $this->maxDelayNum) {
                    $this->status = self::STATUS_FAIL;
                } else {
                    $this->status = self::STATUS_DELAY;
                    $this->delayFrom = time();
                    $this->delayTo = $this->delayFrom + ($this->delay ?? $this->workFlow->delay);
                }
                break;
            case 4:
                // 失败重试
                if ($this->retriedNum >= $this->maxRetryNum) {
                    $this->status = self::STATUS_FAIL;
                } else {
                    $this->status = self::STATUS_RETRY;
                    $this->retryAt = time() + $this->retryInterval[$this->retriedNum + 1];
                }
                break;
            case 5:
                // 致命错误
                $this->status = self::STATUS_FAIL;
                break;
            default:
                throw new InvalidResponseException("非法的响应结果");
                break;
        }
    }

    /**
     * @param array $nodeCfg
     */
    protected function init(array $nodeCfg)
    {
        $this->action = $nodeCfg['action'] ?: $this->name;

        if ($nodeCfg['conditions']) {
            $this->conditions = $nodeCfg['conditions'];
        }

        $this->delay = $nodeCfg['delay'] ?: 5;
        $this->maxRetryNum = $nodeCfg['max_retry_num'] ?: 6;
        $this->maxDelayNum = $nodeCfg['max_delay_num'] ?: 5;
    }
}
