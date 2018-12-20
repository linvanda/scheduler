<?php

namespace Weiche\Scheduler\Server;

use Swoole\Http\Server as HttpServer;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Weiche\Scheduler\Utils\Config;

/**
 * 服务器基类
 * Class Server
 * @package Weiche\Scheduler\Server
 */
abstract class Server
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
