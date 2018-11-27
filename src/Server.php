<?php

namespace Weiche\Scheduler;

use Weiche\Scheduler\Utils\Config;
use Swoole\Http\Server as HttpServer;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * 服务器
 *
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
     *
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

        $this->httpServer = $server;
    }

    public function onStart(HttpServer $server)
    {
        // mac 上不支持设置进程名称
        @swoole_set_process_name('scheduler master process');
    }

    public function onRequest(Request $request, Response $response)
    {
        echo "request\n";
        $response->end("ok");
    }
}
