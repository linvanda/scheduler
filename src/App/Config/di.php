<?php

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\SwiftMailerHandler;

/**
 * 依赖注入配置
 */

return [
    // 服务器。此处决定了工作流服务器模式：task 模式或 coroutine 模式。默认是 coroutine 模式
    'Server' => Scheduler\Server\CoroutineServer::class,
    // 符合 PSR-3 的日志实例
    'Logger' => function () {
        $logger = new Logger('app');

        $handlers = [];
        $handlers[] = new StreamHandler(DATA_PATH . '/log/app.debug.log', Logger::DEBUG);
        $handlers[] = new StreamHandler(DATA_PATH . '/log/app.info.log', Logger::INFO);
        $handlers[] = new StreamHandler(DATA_PATH . '/log/app.error.log', Logger::ERROR);

        $trans = new Swift_SmtpTransport("smtp.exmail.qq.com");
        $trans->setUsername("robot@weicheche.cn")->setPassword("Chechewei123");
        $mailer = new Swift_Mailer($trans);
        $messager = new Swift_Message("test subject");
        $messager->setFrom(["robot@weicheche.cn" => "喂车科技"])->setTo(["songlin.zhang@weicheche.cn"])->setSubject('工作流调度系统告警');
        $emailHandler = new SwiftMailerHandler($mailer, $messager, Logger::CRITICAL);
        $handlers[] = $emailHandler;

        // debug 模式下，在最上层加上 debug handler 打印所有日志到控制台
        if (defined('DEBUG') && DEBUG) {
            unset($handlers[0]);
            $handlers[] = new StreamHandler(STDOUT);
        }

        $levelArr = [
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'error' => Logger::ERROR,
        ];

        foreach ($handlers as $handler) {
            $handler->setBubble(false);
            if ($handler->getLevel() >= $levelArr[LOG_LEVEL]) {
                $logger->pushHandler($handler);
            }
        }

        return $logger;
    }
];
