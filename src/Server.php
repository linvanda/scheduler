<?php

namespace Weiche\Scheduler;

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
    private $httpServer;

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

    private function create($debug = 0)
    {
        // 加载配置文件
    }
}
