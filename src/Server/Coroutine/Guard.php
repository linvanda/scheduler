<?php

namespace Weiche\Scheduler\Server\Coroutine;

use \Swoole\Coroutine as co;
use Weiche\Scheduler\Context\CContext as Context;
use Weiche\Scheduler\Workflow\CoroutineWorkFlow;
use Weiche\Scheduler\Exception\InvalidContextException;

/**
 * 工作流队列守卫，负责从队列中取出工作流并执行
 * 该层负责工作流启动、处理工作流返回信息、日志上报、持久化等任务
 * Class Guard
 * @package Weiche\Scheduler\Server\Coroutine
 */
class Guard
{
    private $cuid;

    /**
     * @param int $timeout
     * @return \Closure
     */
    public function create($timeout = 0)
    {
        return function () use ($timeout) {
            $this->init();

            // 协程退出前的清理工作
            if (function_exists('defer')) {
                defer(function () {
                    $this->destroy();
                });
            }

            // 从工作流队列取出工作流对象并执行
            while (true) {
                if (($workFlow = Context::inst()->workerFlowQueue()->pop()) instanceof CoroutineWorkFlow) {
                    try {
                        $this->pre();
                        $workFlow->run();
                    } catch (\Exception $e) {

                    }

                    $this->post();
                } else {
                    break;
                }
            }

            if (!function_exists('defer')) {
                $this->destroy();
            }
        };
    }

    /**
     * 协程开启后的初始化工作
     * @throws InvalidContextException
     */
    private function init()
    {
        $this->cuid = co::getuid();

        if ($this->cuid < 0) {
            throw new InvalidContextException('请在协程环境中使用 Guard');
        }

        // 将协程添加到上下文环境信息中
        Context::inst()->addCo($this->cuid);
    }

    /**
     * 协程业务真正执行前的钩子
     */
    private function pre()
    {
        // 状态改成忙
        Context::inst()->switchCoToBusy($this->cuid);
    }

    /**
     * 协程业务执行完成后的钩子
     */
    private function post()
    {
        // 状态改成闲
        Context::inst()->switchCoToWait($this->cuid);
    }

    /**
     * 协程退出前的清理工作
     */
    private function destroy()
    {
        // 从上下文中移除协程信息
        Context::inst()->removeCo($this->cuid);
    }
}
