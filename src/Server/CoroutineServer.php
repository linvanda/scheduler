<?php

namespace Weiche\Scheduler\Server;

use Swoole\Http\Server as HttpServer;
use Swoole\Coroutine as co;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Weiche\Scheduler\Utils\Config;
use Weiche\Scheduler\Workflow\CoroutineWorkFlow;
use Weiche\Scheduler\Context\CContext as Context;
use Weiche\Scheduler\Server\Coroutine\Guard;

/**
 * 协程版 Server
 * Class CoroutineServer
 * @package Weiche\Scheduler\Server
 */
class CoroutineServer extends Server
{
    /**
     * 工作进程启动
     * @param HttpServer $server
     * @param $workerId
     */
    public function onWorkerStart(HttpServer $server, $workerId)
    {
        // 初始化消费端协程(基础消费者协程)
        for ($i = 0; $i < Config::get('coroutine_min_workflow', 5); $i++) {
            co::create((new Guard())->create());
        }

        // 定时检查工作流队列情况，如果满了，则创建额外的消费端协程
        $server->tick(5000, function () {
            $context = Context::inst();

            $willCreatNum = min(Config::get('coroutine_create_size'), Config::get('coroutine_max_workflow') - $context->coNum());
            if (
                $context->workerFlowQueue()->length() >= Config::get('coroutine_create_threshold', 10)
                && $context->coNum() < Config::get('coroutine_max_workflow')
                && co::stats()['coroutine_num'] < Config::get('server.max_coroutine') - $willCreatNum
            ) {
                // 增量创建协程消费者，这些消费者需要设置超时时间，防止出现过多等待协程
                for ($i = 0; $i < $willCreatNum; $i++) {
                    co::create((new Guard())->create(Config::get('coroutine_timeout', 60)));
                }
            }
        });
    }

    /**
     * 工作进程结束
     * @param HttpServer $server
     * @param $workerId
     */
    public function onWorkerStop(HttpServer $server, $workerId)
    {
        // 销毁进程上下文
//        Context::destroy($workerId);
    }

    public function onWorkerExit(HttpServer $server, $workerId)
    {

    }

    /**
     * 请求到来
     * @param Request $request
     * @param Response $response
     */
    public function onRequest(Request $request, Response $response)
    {
        echo "request\n";
        $response->end("ok");
    }
}
