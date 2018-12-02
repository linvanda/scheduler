<?php

namespace Weiche\Scheduler;

use Swoole\Http\Server as HttpServer;
use Swoole\Coroutine as co;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Weiche\Scheduler\Utils\Config;
use Weiche\Scheduler\Workflow\WorkFlow;
use Weiche\Scheduler\Context;

/**
 * 服务器
 * Class Server
 * @package Weiche\Scheduler
 */
class Server
{
    /**
     * @var \Swoole\Http\Server
     */
    protected $httpServer;

    public function __construct($debug = 0)
    {
        $this->create($debug);
    }

    /**
     * 启动服务
     */
    public function start()
    {
        return $this->httpServer->start();
    }

    /**
     * server 工厂方法
     * @param int $debug
     */
    protected function create($debug = 0)
    {
        if ($this->httpServer) {
            return;
        }

        $config = Config::get('server');

        if ($debug) {
            $config['daemonize'] = 0;
            $config['log_level'] = SWOOLE_LOG_DEBUG;
        }

        if (!$config['host'] || !$config['port']) {
            throw new \InvalidArgumentException('请提供 host 和 port 信息');
        }

        $server = new HttpServer($config['host'], $config['port']);

        unset($config['host'], $config['port']);
        $server->set($config);

        /**
         * 事件注册
         */
        $server->on('start', [$this, 'onStart']);
        $server->on('request', [$this, 'onRequest']);
        $server->on('workerStart', [$this, 'onWorkerStart']);
        $server->on('workerStop', [$this, 'onWorkerStop']);

        $this->httpServer = $server;
    }

    /**
     * 服务启动
     * @param HttpServer $server
     */
    public function onStart(HttpServer $server)
    {
        // mac 上不支持设置进程名称
        @swoole_set_process_name('scheduler master process');
    }

    /**
     * 工作进程启动
     * @param HttpServer $server
     * @param $workerId
     */
    public function onWorkerStart(HttpServer $server, $workerId)
    {
        // 协程消费端
        $customerFunc = function () {
            Context::instance()->customerIncr();

            // 从工作流队列取出工作流对象并执行
            while (true) {
                if (($workFlow = Context::instance()->workerFlowQueue()->pop()) instanceof WorkFlow) {
                    $workFlow->run();
                } else {
                    break;
                }
            }

            Context::instance()->customerDecr();
        };

        // 初始化消费端协程
        for ($i = 0; $i < Config::get('min_workflow_coroutine', 5); $i++) {
            co::create($customerFunc);
        }

        // 定时检查工作流队列情况，如果满了，则创建额外的消费端协程
        $server->tick(1000, function () use ($customerFunc) {
            $context = Context::instance();

            if (
                $context->workerFlowQueue()->length() >= Config::get('coroutine_create_threshold', 10)
                && $context->customerNum() < Config::get('max_workflow_coroutine')
            ) {
                // 增量创建协程
                for (
                    $i = 0;
                    $i < min(Config::get('coroutine_create_size'), Config::get('max_workflow_coroutine') - $context->customerNum());
                    $i++
                ) {
                    co::create($customerFunc);
                }
            } elseif (
                $context->workerFlowQueue()->isEmpty()
            ) {
                // 是否需要清理协程
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
