<?php

namespace Weiche\Scheduler\Server\Coroutine;

use \Swoole\Coroutine as co;
use Weiche\Scheduler\Context\CContext as Context;
use Weiche\Scheduler\Workflow\CoroutineWorkFlow;
use Weiche\Scheduler\Exception\InvalidContextException;

/**
 * 工作流队列守卫，负责从队列中取出工作流并执行
 * Class Guard
 * @package Weiche\Scheduler\Server\Coroutine
 */
class Guard
{
    private $cuid;

    /**
     * @throws InvalidContextException
     */
    public function run()
    {
        $this->pre();

        // 从工作流队列取出工作流对象并执行
        while (true) {
            if (($workFlow = Context::inst()->workerFlowQueue()->pop()) instanceof CoroutineWorkFlow) {
                // 状态改成忙
                Context::inst()->switchCoToBusy($this->cuid);
                try {
                    $workFlow->run();
                } catch (\Exception $e) {

                }

                // 状态改成闲
                Context::inst()->switchCoToWait($this->cuid);
            } else {
                break;
            }
        }

        $this->post();
    }

    /**
     * @throws InvalidContextException
     */
    private function pre()
    {
        $this->cuid = co::getuid();

        if ($this->cuid < 0) {
            throw new InvalidContextException('请在协程环境中使用 Guard');
        }

        Context::inst()->addCo($this->cuid);
    }

    private function post()
    {
        Context::inst()->removeCo($this->cuid);
    }
}
