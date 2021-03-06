<?php

namespace Scheduler\Server\Coroutine;

use Scheduler\Fundation\Logger;
use Scheduler\Workflow\Node;
use \Swoole\Coroutine as co;
use Scheduler\Context\CContext as Context;
use Scheduler\Workflow\CoroutineWorkFlow;
use Scheduler\Exception\InvalidContextException;
use Scheduler\Workflow\WorkFlow;

/**
 * 工作流队列守卫，负责从队列中取出工作流并执行
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
                if (($workFlow = Context::workerFlowQueue()->pop()) instanceof CoroutineWorkFlow) {
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
            Logger::emergency("工作流执行异常", ['workflow' => $workFlow->name()]);
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
        Context::addCo($this->cuid);
    }

    /**
     * 协程业务真正执行前的钩子
     */
    private function pre()
    {
        // 状态改成忙
        Context::switchCoToBusy($this->cuid);
    }

    /**
     * 协程业务执行完成后的钩子
     * @param WorkFlow $workFlow
     */
    private function post(WorkFlow $workFlow)
    {
        //TODO 持久化工作流信息

        // 根据工作流的状态决定是立即执行下阶段、延迟加入到队列中还是结束
        if ($workFlow->willContinue()) {
            if (($waitTime = $workFlow->nextExecTime() - time()) > 1) {
                Logger::debug("工作流下次执行时间：{$workFlow->nextExecTime()}，将在{$waitTime}秒后再次执行");
                swoole_timer_after($waitTime * 1000, function () use ($workFlow) {
                    Context::workerFlowQueue()->push($workFlow);
                });
            } else {
                Logger::debug("工作流立即执行下一批次");
                $this->run($workFlow);
            }
        } else {
            // 工作流执行完成
            Logger::debug(
                "工作流{$workFlow->name()}执行完成",
                [
                    'nodes' => array_map(function (Node $node) {
                        return [
                            'status' => $node->status(),
                            'msg' => $node->response()->getMessage(),
                            'desc' => $node->response()->getDesc()
                        ];
                    }, $workFlow->nodes())
                ]
            );
        }

        // 协程状态改成闲
        Context::switchCoToWait($this->cuid);
    }

    /**
     * 协程退出前的清理工作
     */
    private function destroy()
    {
        // 从上下文中移除协程信息
        Context::removeCo($this->cuid);
        Logger::debug("协程退出:{$this->cuid}");
    }
}
