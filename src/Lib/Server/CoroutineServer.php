<?php

namespace Scheduler\Server;

use Scheduler\Infrastructure\Logger;
use Swoole\Http\Server as HttpServer;
use Swoole\Coroutine as co;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Runtime;
use Scheduler\Infrastructure\IRouter;
use Scheduler\Utils\Config;
use Scheduler\Context\CContext as Context;
use Scheduler\Server\Coroutine\Guard;
use Scheduler\Infrastructure\Container;
use Scheduler\Workflow\CoroutineWorkFlow;

/**
 * 协程版 Server
 * Class CoroutineServer
 * @package Scheduler\Server
 */
class CoroutineServer extends Server
{
    /**
     * 工作进程启动
     * @param HttpServer $server
     * @param $workerId
     * @throws \Scheduler\Exception\FileNotFoundException
     */
    public function onWorkerStart(HttpServer $server, $workerId)
    {
        // 试图将 PHP 内置 IO 函数协程化
        if (method_exists('\Swoole\Runtime', 'enableCoroutine')) {
            Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL);
        }

        // 初始化进程上下文
        Context::init();
        // 注入工作流
        Container::set("Workflow", CoroutineWorkFlow::class);

        // 初始化消费端协程(基础消费者协程)
        for ($i = 0; $i < Config::get('co_min_workflow', 5); $i++) {
            co::create((new Guard())->create());
        }

        // 定时检查工作流队列情况，如果满了，则创建额外的消费端协程
        $server->tick(5000, function () {
            $willCreatNum = min(Config::get('co_create_size'), Config::get('co_max_workflow') - Context::coNum());
            if (
                Context::workerFlowQueue()->length() >= Config::get('co_create_threshold', 10)
                && Context::coNum() < Config::get('co_max_workflow')
                && co::stats()['coroutine_num'] < Config::get('server.max_coroutine') - $willCreatNum
            ) {
                // 增量创建协程消费者，这些消费者需要设置超时时间，防止出现过多等待协程
                for ($i = 0; $i < $willCreatNum; $i++) {
                    co::create((new Guard())->create(Config::get('co_timeout', 60)));
                }

                Logger::debug("动态增加协程数：{$willCreatNum}");
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
        Context::destroy();
    }

    /**
     * @param HttpServer $server
     * @param $workerId
     * @throws \Exception
     */
    public function onWorkerExit(HttpServer $server, $workerId)
    {
    }

    /**
     * 请求到来，创建工作流对象并加入消费队列中
     * @param Request $request
     * @param Response $response
     */
    public function onRequest(Request $request, Response $response)
    {
        Logger::debug("请求到来:", ['request' => $request->rawcontent()]);

        // 如果消费队列满了，则直接返回错误
        if (Context::workerFlowQueue()->isFull()) {
            $response->status(403);
            $response->end("workflow queue is full");

            Logger::error("消费队列满了，拒绝请求");

            return;
        }

        try {
            /** @var IRouter $router 路由解析*/
            $router = Container::make('Router', ['request' => $request]);

            Logger::debug("将工作流{$router->workflow()->name()}加入到消费队列中");

            // 将工作流加入到队列中
            Context::workerFlowQueue()->push($router->workflow());
            $response->end(json_encode(['code' => 200, 'msg' => 'ok']));
        } catch (\Exception $e) {
            Logger::emergency("请求解析异常", ['msg' => $e->getMessage()]);

            $response->status(500);
            $response->end($e->getMessage());
        }
    }
}
