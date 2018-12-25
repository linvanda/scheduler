<?php

return [
    // server 中的配置都是可以调用 Swoole\Server::set() 设置的
    'server' => [
        'host' => '127.0.0.1',
        'port' => '9876',
        'worker_num' => 8,
        // 每个 worker 进程可以创建的最大协程数
        'max_coroutine' => 6000,
        // 请求分发策略：轮询
        'dispatch_mode' => 1,
        // 注意：使用 systemd 管理该服务时，不要设置成 daemonize 模式
        'daemonize' => 1,
        'log_file' => DATA_PATH . '/log/server.log',
        'log_level' => SWOOLE_LOG_WARNING,
        'open_cpu_affinity' => 1,
        // 当服务以 root 用户启动时，worker 进程所属的用户和组（但建议不要用 root 启动服务）
        'user' => 'www',
        'group' => 'www',
        'chroot' => ROOT_PATH,
        // 注意如果Server非正常结束，PID文件不会删除，需要使用swoole_process::kill($pid, 0)来侦测进程是否真的存在
        'pid_file' => DATA_PATH . '/master.pid',
        'reload_async' => true,
        'max_wait_time' => 60,
    ],
    // 每个进程工作流排队缓冲区大小
    'co_workflow_buffer_size' => 1024,
    // 每个进程最小消费协程数量，服务启动时创建的消费者协程个数
    'co_min_workflow' => 5,
    // 每个进程最多允许启动多少个协程处理工作流，该值不能大于 server.max_coroutine
    'co_max_workflow' => 2000,
    // 当工作流等待队列中等待元素数大于此值时开始增量创建消费者协程，需要小于co_workflow_buffer_size
    'co_create_threshold' => 15,
    // 增量创建消费者协程时每次创建的数量
    'co_create_size' => 10,
    // 消费者协程等待超时时间
    'co_timeout' => 60,
    'redis' => [
        'host' => '192.168.1.45',
    ],
    'mysql' => [
        'host' => '0.0.0.0'
    ],
    // 日志等级：debug、info、error
    'log_level' => 'info',
];
