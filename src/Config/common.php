<?php

return [
    // http server 的配置
    'server' => [
        'host' => '127.0.0.1',
        'port' => '9876',
        'worker_num' => 16,
        // 每个 worker 进程可以创建的最大协程数
        'max_coroutine' => 4000,
        'dispatch_mode' => 1,
        // 注意：使用 systemd 管理该服务时，不要设置成 daemonize 模式
        'daemonize' => 1,
        'log_file' => DATA_PATH . '/log/server.log',
        'log_level' => SWOOLE_LOG_WARNING,
        'open_cpu_affinity' => 1,
        // 当 服务以 root 用户启动时，worker 进程所属的用户和组（但建议不要用 root 启动服务）
        'user' => 'www',
        'group' => 'www',
        'chroot' => ROOT_PATH,
        // 注意如果Server非正常结束，PID文件不会删除，需要使用swoole_process::kill($pid, 0)来侦测进程是否真的存在
        'pid_file' => DATA_PATH . '/server.pid',
        // 重启策略
        'max_request' => 5000,
        'reload_async' => true,
        'max_wait_time' => 60,
    ]
];
