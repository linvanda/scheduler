<?php

namespace Weiche\Scheduler\Server;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as HttpServer;

/**
 * worker -> task 多进程模式
 * Class TaskServer
 * @package Weiche\Scheduler\Server
 */
class TaskServer extends Server
{

    /**
     * 工作进程启动
     * @param HttpServer $server
     * @param $workerId
     */
    public function onWorkerStart(HttpServer $server, $workerId)
    {
        echo 'todo';
    }

    /**
     * 工作进程结束
     * @param HttpServer $server
     * @param $workerId
     */
    public function onWorkerStop(HttpServer $server, $workerId)
    {
        // TODO: Implement onWorkerStop() method.
    }

    /**
     * 请求到来
     * @param Request $request
     * @param Response $response
     */
    public function onRequest(Request $request, Response $response)
    {
        // TODO: Implement onRequest() method.
    }
}
