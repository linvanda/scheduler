<?php

namespace Scheduler\Workflow;

use Scheduler\Controller;
use Scheduler\Infrastructure\Response\FatalResponse;
use Scheduler\Infrastructure\Request;
use Scheduler\Infrastructure\Response\NoneResponse;
use Scheduler\Infrastructure\Response\Response;
use Scheduler\Exception\InvalidResponseException;

/**
 * 工作流节点基类
 * Interface Node
 * @package Scheduler\Workflow
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
     * @throws InvalidResponseException
     * @throws \Scheduler\Exception\InvalidCallException
     */
    public function run(Controller $controller, Request $request, array $prevResponse = [])
    {
        $this->pre();
        // 启动控制器
        $this->response = $controller->handler($this->action, $request, $prevResponse);
        $this->post();
    }

    /**
     * 获取节点的执行结果，如果未执行，则返回 NoneResponse
     * @return \Scheduler\Infrastructure\Response\Response
     */
    public function response()
    {
        return $this->response ?: new NoneResponse();
    }

    public function name()
    {
        return $this->name;
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
     * 节点前置条件配置
     * @return array
     */
    public function conditions()
    {
        return $this->conditions;
    }

    /**
     * 节点是否 sleep 态（被 delay 的或 retry 的且未到时间）
     * @return bool
     */
    public function isSleep()
    {
        $now = time();

        if (
            $this->status === self::STATUS_DELAY && $this->delayTo > $now ||
            $this->status === self::STATUS_RETRY && $this->retryAt > $now
        ) {
            return true;
        }

        return false;
    }

    /**
     * 节点是否处于完成态（执行成功或失败）
     * 非完成态：初始态、进行中、延迟中、待重试
     */
    public function isFinished()
    {
        return $this->status === self::STATUS_SUC || $this->status === self::STATUS_FAIL;
    }

    /**
     * 节点是否正在执行
     * @return bool
     */
    public function isExecuting()
    {
        return $this->status === self::STATUS_DOING;
    }

    /**
     * 节点是否处于执行态：正在执行、延迟执行或失败待重试
     */
    public function isExecute()
    {
        return in_array($this->status, [self::STATUS_DOING, self::STATUS_DELAY, self::STATUS_RETRY]);
    }

    public function isSuc()
    {
        return $this->status === self::STATUS_SUC;
    }

    public function isFail()
    {
        return $this->status === self::STATUS_FAIL;
    }

    /**
     * 节点在延迟执行或重试的状态下，下次执行的时间
     * @return int
     */
    public function nextExecTime()
    {
        if ($this->status === self::STATUS_RETRY || $this->status === self::STATUS_DELAY) {
            return min($this->delayTo, $this->retryAt);
        }

        return 0;
    }

    public function fail($errMsg = '', $desc = '')
    {
        $this->status = self::STATUS_FAIL;
        if (!$this->response) {
            $this->response = new FatalResponse([], $errMsg, $desc);
        }
    }

    /**
     * 节点是否会被某个节点阻塞
     * @param Node $blockNode
     * @return bool
     */
    public function willBeBlocked(Node $blockNode)
    {
        if ($this->name == $blockNode->name()) {
            return false;
        }

        $preResponseCode = $this->conditions[$blockNode->name()];

        if ($preResponseCode === null) {
            return false;
        }

        // 未完成的节点一定阻塞后续节点
        if (!$blockNode->isFinished()) {
            return true;
        }

        if ($preResponseCode) {
            $preCode = $blockNode->response()->getCode();

            if (is_string($preResponseCode)) {
                return strpos($preCode, rtrim($preResponseCode, '*')) !== 0;
            }

            if (is_array($preResponseCode)) {
                return !in_array($preCode, $preResponseCode);
            }

            return $preCode != $preResponseCode;
        }

        return false;
    }

    /**
     * 控制器执行前
     */
    protected function pre()
    {
        $this->response = null;

        // 如果是失败重试或延迟执行，则将相关次数加1
        if ($this->status === self::STATUS_RETRY) {
            $this->retriedNum++;
        } elseif ($this->status === self::STATUS_DELAY) {
            $this->delayedNum++;
        }

        $this->status = self::STATUS_DOING;

        $this->delayFrom = 0;
        $this->delayTo = 0;
        $this->retryAt = 0;
    }

    /**
     * 控制器执行完毕后
     * @throws InvalidResponseException
     */
    protected function post()
    {
        if (!$this->response) {
            return;
        }

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
        $this->conditions = $nodeCfg['conditions'] ?: [];
        $this->delay = $nodeCfg['delay'] ?: 5;
        $this->maxRetryNum = $nodeCfg['max_retry_num'] ?: 6;
        $this->maxDelayNum = $nodeCfg['max_delay_num'] ?: 5;
    }
}
