<?php

namespace Scheduler\Server\Coroutine;

use \Swoole\Coroutine as co;
use Scheduler\Context\CContext as Context;
use Scheduler\Workflow\CoroutineWorkFlow;
use Scheduler\Exception\InvalidContextException;
use Scheduler\Workflow\WorkFlow;

/**
 * 工作流队列守卫，负责从队列中取出工作流并执行
 * 该层负责工作流启动、处理工作流返回信息、日志上报、持久化等任务
 * Guard 不能向外抛异常，否则整个进程会退出，必须捕获内部抛出的所有异常
 *
 * Class Guard
 * @package Scheduler\Server\Coroutine
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

            /**
             * 从工作流队列取出工作流对象并执行
             */
            while (true) {
                if (($workFlow = Context::inst()->workerFlowQueue()->pop()) instanceof CoroutineWorkFlow) {
                    $this->run($workFlow);
                } else {
                    break;
                }
            }

            if (!function_exists('defer')) {
                $this->destroy();
            }
        };
    }

    private function run(WorkFlow $workFlow)
    {
        try {
            $this->pre();
            $workFlow->run();
        } catch (\Exception $e) {
            //TODO 记录异常日志，并停止该工作流的执行
            $workFlow->fail();
        } finally {
            $this->post($workFlow);
        }
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
     * @param WorkFlow $workFlow
     */
    private function post(WorkFlow $workFlow)
    {
        //TODO 持久化工作流信息

        // 协程状态改成闲
        Context::inst()->switchCoToWait($this->cuid);

        // 根据工作流的状态决定是立即执行下阶段、延迟加入到队列中还是结束
        if ($workFlow->willContinue()) {
            if ($nextTime = $workFlow->nextExecTime()) {
                swoole_timer_after($nextTime * 1000, function () use ($workFlow) {
                    Context::inst()->workerFlowQueue()->push($workFlow);
                });
            } else {
                $this->run($workFlow);
            }
        }
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
