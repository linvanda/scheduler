<?php

namespace Scheduler\Context;

use Swoole\Coroutine as co;
use Scheduler\Utils\Config;

/**
 * 协程版进程上下文，保存 worker 进程级别的全局对象
 * Class Context
 * @package Scheduler\Context
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

    /**
     * CContext constructor.
     * @throws \Exception
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    private function __construct()
    {
        // 创建协程 Channel 排队待执行工作流
        $this->workFlowQueue = new co\Channel(Config::get('co_workflow_buffer_size', 1024));
        // 消费协程数
        $this->coNum = 0;
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
     * 初始化
     */
    public static function init()
    {
        if (!self::$context) {
            self::$context = new self();
        }
    }

    /**
     * 工作流队列
     * @return co\Channel
     */
    public static function workerFlowQueue()
    {
        return self::$context->workFlowQueue;
    }

    /**
     * 当前协程消费端数量
     * @return int
     */
    public static function coNum()
    {
        return self::$context->coWaitNum + self::$context->coBusyNum;
    }

    /**
     * 添加协程
     * @param $cuid
     */
    public static function addCo($cuid)
    {
        self::$context->coStatus[$cuid] = self::CO_STATUS_WAIT;
        self::$context->coWaitNum++;
    }

    /**
     * 移除协程
     */
    public static function removeCo($cuid)
    {
        if (self::$context->coStatus[$cuid] ==  self::CO_STATUS_WAIT) {
            self::$context->coWaitNum--;
        } else {
            self::$context->coBusyNum--;
        }

        unset(self::$context->coStatus[$cuid]);
    }

    /**
     * 协程切换成闲态
     * @param $cuid
     */
    public static function switchCoToWait($cuid)
    {
        if ($cuid < 0 || !isset(self::$context->coStatus[$cuid]) || self::$context->coStatus[$cuid] === self::CO_STATUS_WAIT) {
            return;
        }

        self::$context->coStatus[$cuid] = self::CO_STATUS_WAIT;
        self::$context->coBusyNum--;
        self::$context->coWaitNum++;
    }

    /**
     * 协程切换成忙态
     * @param $cuid
     */
    public static function switchCoToBusy($cuid)
    {
        if ($cuid < 0 || !isset(self::$context->coStatus[$cuid]) || self::$context->coStatus[$cuid] === self::CO_STATUS_BUSY) {
            return;
        }

        self::$context->coStatus[$cuid] = self::CO_STATUS_BUSY;
        self::$context->coWaitNum--;
        self::$context->coBusyNum++;
    }
}
