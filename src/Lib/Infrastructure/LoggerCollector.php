<?php

namespace Scheduler\Infrastructure;

use Swoole\Process;
use Monolog\Logger as MonoLogger;
use Scheduler\Utils\Config;

/**
 * 日志收集程序
 * 该类和 Logger 类配合使用
 * Class LoggerCollector
 * @package Scheduler\Infrastructure
 */
class LoggerCollector
{
    /**
     * @var Process
     */
    protected $process;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * LoggerCollector constructor.
     * @param Process $process
     * @throws
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
        $this->logger = Container::get('Logger');
    }

    public function __invoke()
    {
        if ($data = $this->process->pop()) {
            $data = json_decode($data, true);
            $level = $data['level'];
            if (method_exists($this->logger, $level)) {
                $this->logger->$level($data['message'], $data['context']);
            }
        }
    }
}
