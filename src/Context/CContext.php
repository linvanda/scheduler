<?php

namespace Weiche\Scheduler\Context;

use Swoole\Coroutine as co;
use Weiche\Scheduler\Utils\Config;

/**
 * 协程版进程上下文，保存 worker 进程级别的全局对象
 * Class Context
 * @package Weiche\Scheduler\Context
 */
class CContext
{
    const CO_STATUS_WAIT = 0;
    const CO_STATUS_BUSY = 1;

    /**
     * @var CContext
     */
    private static $context;

    /**
     * 工作流队列
     * @var co\Channel
     */
    private $workFlowQueue;

    // 消费协程状态：0：等待；1：执行任务中
    private $coStatus = [];

    // 当前等待中的协程数
    private $coWaitNum = 0;

    // 当前执行任务中的协程数
    private $coBusyNum = 0;

    private function __construct()
    {
        // 创建协程 Channel 排队待执行工作流
        $this->workFlowQueue = new co\Channel(Config::get('coroutine_workflow_buffer_size', 1024));
        // 消费协程数
        $this->coNum = 0;
    }

    public function __destruct()
    {
        //TODO: 释放外部资源
    }

    /**
     * 销毁某进程上下文
     */
    public static function destroy()
    {
        if (self::$context) {
            self::$context = null;
        }
    }

    /**
     * 获取某进程上下文
     * @return CContext
     */
    public static function inst()
    {
        if (!self::$context) {
            self::$context = new self();
        }

        return self::$context;
    }

    /**
     * 工作流队列
     * @return co\Channel
     */
    public function workerFlowQueue()
    {
        return $this->workFlowQueue;
    }

    /**
     * 当前协程消费端数量
     * @return int
     */
    public function coNum()
    {
        return $this->coWaitNum + $this->coBusyNum;
    }

    /**
     * 添加协程
     * @param $cuid
     */
    public function addCo($cuid)
    {
        $this->coStatus[$cuid] = self::CO_STATUS_WAIT;
        $this->coWaitNum++;
    }

    /**
     * 移除协程
     */
    public function removeCo($cuid)
    {
        if ($this->coStatus[$cuid] ==  self::CO_STATUS_WAIT) {
            $this->coWaitNum--;
        } else {
            $this->coBusyNum--;
        }

        unset($this->coStatus[$cuid]);
    }

    /**
     * 协程切换成闲态
     * @param $cuid
     */
    public function switchCoToWait($cuid)
    {
        if ($cuid < 0 || !isset($this->coStatus[$cuid]) || $this->coStatus[$cuid] === self::CO_STATUS_WAIT) {
            return;
        }

        $this->coStatus[$cuid] = self::CO_STATUS_WAIT;
        $this->coBusyNum--;
        $this->coWaitNum++;
    }

    /**
     * 协程切换成忙态
     * @param $cuid
     */
    public function switchCoToBusy($cuid)
    {
        if ($cuid < 0 || !isset($this->coStatus[$cuid]) || $this->coStatus[$cuid] === self::CO_STATUS_BUSY) {
            return;
        }

        $this->coStatus[$cuid] = self::CO_STATUS_BUSY;
        $this->coWaitNum--;
        $this->coBusyNum++;
    }

    /**
     * 清理等待队列上的多余协程
     * @param int $remainNum
     */
    public function cleanWaitedCo($remainNum = 300)
    {
        if (!isset($remainNum) || $remainNum < 0) {
            $remainNum = 0;
        }

        foreach ($this->coStatus as $cuid => $status) {
            if ($status === self::CO_STATUS_WAIT) {
                $this->removeCo($cuid);

                if ($this->coWaitNum <= $remainNum) {
                    break;
                }
            }
        }
    }
}
