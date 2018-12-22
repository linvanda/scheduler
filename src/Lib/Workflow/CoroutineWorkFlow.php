<?php

namespace Scheduler\Workflow;

use Scheduler\Utils\Config;
use Swoole\Coroutine\Channel;
use Scheduler\Infrastructure\Request;
use Swoole\Coroutine as co;

/**
 * 协程版工作流
 * Class CoroutineWorkFlow
 * @package Scheduler\Workflow
 */
class CoroutineWorkFlow extends WorkFlow
{
    protected $waitChannel;
    protected $runNodeNum = 0;

    /**
     * CoroutineWorkFlow constructor.
     * @param string $name
     * @param Request $request
     * @throws \Scheduler\Exception\ClassNotFoundException
     * @throws \Scheduler\Exception\FileNotFoundException
     * @throws \Scheduler\Exception\InvalidConfigException
     */
    public function __construct(string $name, Request $request)
    {
        parent::__construct($name, $request);
        $this->waitChannel = new Channel(count($this->nodes));
    }

    /**
     * @throws \Scheduler\Exception\WorkFlowException
     */
    protected function pre()
    {
        parent::pre();

        // 清空等待通道
        while (!$this->waitChannel->isEmpty()) {
            $this->waitChannel->pop();
        }

        // 重置计数器
        $this->runNodeNum = 0;
    }

    /**
     * 协程版工作流并行执行所有可执行节点后，需要等待节点完成
     * @return mixed|void
     * @throws \Scheduler\Exception\InvalidConfigException
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    protected function runNodes()
    {
        parent::runNodes();

        // 进入等待状态
        $timeout = Config::get('max_node_run_time', 300);
        while ($this->waitChannel->pop($timeout)) {
            $this->runNodeNum--;
            if ($this->runNodeNum <= 0) {
                break;
            }
        }
    }

    /**
     * 在独立的协程中执行自节点
     * @param Node $node
     */
    protected function runNode(Node $node)
    {
        $this->runNodeNum++;

        co::create(function () use ($node) {
            // 需要捕获里层抛出的异常，否则协程外面是捕获不到的，直接导致该进程退出
            try {
                $node->run($this->controller, $this->request, $this->getPrevNodeResponse($node));
            } catch (\Exception $e) {
                // 将节点设置为执行失败
                $node->fail($e->getMessage(), $e->getTraceAsString());
            } finally {
                // 发送完成信号
                $this->waitChannel->push(1);
            }
        });
    }
}
