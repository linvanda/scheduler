<?php

namespace Weiche\Scheduler;

use Swoole\Coroutine as co;
use Weiche\Scheduler\Utils\Config;

/**
 * 进程上下文，保存 worker 进程级别的全局对象
 * Class Context
 * @package Weiche\Scheduler
 */
class Context
{
    /**
     * @var Context
     */
    private static $context;

    /**
     * 工作流队列
     * @var co\Channel
     */
    private $workFlowQueue;

    /**
     * 当前消费者协程数
     * @var int
     */
    private $customerNum = 0;

    /**
     * 数据库池
     * @var co\Channel
     */
    private $dbPool;

    private function __construct()
    {
        // 创建协程 Channel 排队待执行工作流
        $this->workFlowQueue = new co\Channel(Config::get('workflow_buffer_size', 1024));
        // 消费协程数
        $this->customerNum = 0;
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
     * @return Context
     */
    public static function instance()
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

    public function customerNum()
    {
        return $this->customerNum;
    }

    public function customerIncr()
    {
        $this->customerNum++;
    }

    public function customerDecr()
    {
        $this->customerNum--;
    }
}
