<?php

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\SwiftMailerHandler;
use \Monolog\Formatter\HtmlFormatter;

/**
 * 依赖注入配置
 */

return [
    // 服务器。此处决定了工作流服务器模式：task 模式或 coroutine 模式。默认是 coroutine 模式
    'Server' => Scheduler\Server\CoroutineServer::class,
    // 符合 PSR-3 的日志实例
    'Logger' => function () {
        $logger = new Logger('scheduler');

        $handlers = [];
        $handlers[] = DEBUG ? new StreamHandler(STDOUT, Logger::DEBUG) : new StreamHandler(DATA_PATH . '/log/app.log', Logger::DEBUG);
        $handlers[] = new StreamHandler(DATA_PATH . '/log/app.info.log', Logger::INFO);
        $handlers[] = new StreamHandler(DATA_PATH . '/log/app.error.log', Logger::ERROR);

        $trans = new Swift_SmtpTransport("smtp.exmail.qq.com");
        $trans->setUsername("robot@weicheche.cn")->setPassword("Chechewei123");
        $mailer = new Swift_Mailer($trans);
        $messager = new Swift_Message("test subject");
        $messager->setFrom(["robot@weicheche.cn" => "from"])->setTo(["songlin.zhang@weicheche.cn"]);
        $emailHandler = new SwiftMailerHandler($mailer, $messager, Logger::CRITICAL);
        $emailHandler->setFormatter(new HtmlFormatter("Y-m-d H:i:s"));
        $handlers[] = $emailHandler;

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
