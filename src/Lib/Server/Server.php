<?php

namespace Scheduler\Server;

use Scheduler\Infrastructure\Container;
use Scheduler\Infrastructure\Logger;
use Scheduler\Infrastructure\LoggerCollector;
use Scheduler\Infrastructure\StatsCollector;
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
     * @throws \Exception
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
        $server->addProcess(new Process(
            function (Process $process) {
                swoole_timer_tick(50, new LoggerCollector());
            },
            false,
            0
        ));

        // 自定义进程：系统状态数据收集
        $server->addProcess(new Process(
            function (Process $process) {
                swoole_timer_tick(1000, new StatsCollector());
            },
            false,
            0
        ));

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

    public function onWorkerError(HttpServer $server, int $workerId, int $workerPid, int $exitCode, int $signal)
    {
        Logger::error('进程异常退出', [
           'worker_id' => $workerId,
            'worker_pid' => $workerPid,
            'exit_code' => $exitCode,
            'signal' => $signal
        ]);
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
