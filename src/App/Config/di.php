<?php

/**
 * 依赖注入配置
 */

return [
    // 服务器。此处决定了工作流服务器模式：task 模式或 coroutine 模式。默认是 coroutine 模式
    'Server' => Scheduler\Server\CoroutineServer::class,
//    'Router' => App\Infrastructure\Router::class,
];
