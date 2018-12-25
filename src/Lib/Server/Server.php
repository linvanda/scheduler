<?php

namespace Scheduler\Server;

use Scheduler\Infrastructure\Container;
use Scheduler\Infrastructure\Logger;
use Scheduler\Infrastructure\LoggerCollector;
use Swoole\Http\Server as HttpServer;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Scheduler\Utils\Config;
use Scheduler\Context\GContext;
use Swoole\Process;

/**
 * 服务器基类
 * Class Server
 * @package Scheduler\Server
 */
abstract class Server
{
    /**
     * @var \Swoole\Http\Server
     */
    protected $httpServer;

    /**
     * Server constructor.
     * @param int $debug
     * @throws \Scheduler\Exception\FileNotFoundException
     */
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
     * 工厂方法
     * @param int $debug
     * @throws \Scheduler\Exception\FileNotFoundException
     * @throws \Exception
     */
    protected function create($debug = 0)
    {
        if ($this->httpServer) {
            return;
        }

        // 初始化全局上下文环境
        GContext::init();

        $config = Config::get('server');

        if ($debug) {
            $config['daemonize'] = 0;
            $config['log_level'] = SWOOLE_LOG_DEBUG;
        }

        if (!$config['host'] || !$config['port']) {
            throw new \InvalidArgumentException('请提供 host 和 port 信息');
        }

        // 创建 http 服务器
        $server = new HttpServer($config['host'], $config['port']);
        unset($config['host'], $config['port']);
        $server->set($config);

        // 自定义进程：日志收集
        $loggerProcess = new Process(
            function (Process $process) use ($server) {
                // 启用消息队列通讯模式(非阻塞模式)
                if (!$process->useQueue(null, 2 | Process::IPC_NOWAIT)) {
                    echo "日志进程创建消息队列失败，服务退出";
                    $server->shutdown();
                }

                unset($server);

                // 定时处理日志队列
                swoole_timer_tick(100, new LoggerCollector($process));
            },
            false,
            0);
        Logger::init($loggerProcess);
        $server->addProcess($loggerProcess);

        /**
         * 事件注册
         */
        $server->on('start', [$this, 'onStart']);
        $server->on('shutdown', [$this, 'onShutdown']);
        $server->on('request', [$this, 'onRequest']);
        $server->on('workerStart', [$this, 'onWorkerStart']);
        $server->on('workerStop', [$this, 'onWorkerStop']);
        $server->on('workerError', [$this, 'onWorkerError']);

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

    public function onShutdown(HttpServer $server)
    {

    }

    public function onWorkerError(HttpServer $server)
    {
        // 记录错误日志
        echo "work error==";
    }

    /**
     * 工作进程启动
     * @param HttpServer $server
     * @param $workerId
     */
    abstract public function onWorkerStart(HttpServer $server, $workerId);

    /**
     * 工作进程结束
     * @param HttpServer $server
     * @param $workerId
     */
    abstract public function onWorkerStop(HttpServer $server, $workerId);

    /**
     * 请求到来
     * @param Request $request
     * @param Response $response
     */
    abstract public function onRequest(Request $request, Response $response);
}
