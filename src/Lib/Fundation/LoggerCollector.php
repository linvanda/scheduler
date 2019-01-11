<?php

namespace Scheduler\Fundation;

use Scheduler\Context\GContext;

/**
 * 日志收集程序
 * 该类和 Logger 类配合使用
 * Class LoggerCollector
 * @package Scheduler\Fundation
 */
class LoggerCollector
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * LoggerCollector constructor.
     * @throws
     */
    public function __construct()
    {
        $this->logger = Container::get('Logger');
    }

    /**
     * 从通道中取日志数据并记录
     */
    public function __invoke()
    {
        if ($data = GContext::loggerChannel()->pop()) {
            $level = $data['level'];
            if (method_exists($this->logger, $level)) {
                $this->logger->$level($data['message'], $data['context']);
            }
        }
    }
}
