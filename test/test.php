<?php

use Swoole\Coroutine as co;
use Scheduler\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * 命令行执行的入口程序
 */

error_reporting(E_ALL ^ E_NOTICE);

// 常量定义
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('APP_PATH', ROOT_PATH . '/src/App');
define('CONFIG_PATH', APP_PATH . '/Config');
define('DATA_PATH', ROOT_PATH . '/data');
define('ENV_DEV', 'dev');
define('ENV_TEST', 'test');
define('ENV_PREVIEW', 'preview');
define('ENV_PRODUCTION', 'production');

define('ENV', 'dev');
require(ROOT_PATH . '/vendor/autoload.php');

class A
{
    public static function b()
    {
        self::f();
    }

    public static function f()
    {

    }
}





$server = new Swoole\Server('0.0.0.0', '4578');
$server->set([
    'log_level' => SWOOLE_LOG_ERROR,
    'worker_num' => 1,
]);
$server->on('WorkerStart', function ($server) {
    echo "--1--:".microtime(true) ."\n";
    co::create(function () {
        $logger = new Logger("test");

        $infoHandler = new StreamHandler(DATA_PATH . '/log/info.log', Logger::INFO);
        $infoHandler->setBubble(false);
        $debugHandler = new StreamHandler(STDOUT, Logger::DEBUG);
        $debugHandler->setBubble(false);
        $errHandler = new StreamHandler(DATA_PATH . '/log/error.log', Logger::ERROR);
        $errHandler->setBubble(false);


        $trans = new Swift_SmtpTransport("smtp.exmail.qq.com");
        $trans->setUsername("robot@weicheche.cn")->setPassword("Chechewei123");
        $mailer = new Swift_Mailer($trans);
        $messager = new Swift_Message("test subject");
        $messager->setFrom(["robot@weicheche.cn" => "from"])->setTo(["songlin.zhang@weicheche.cn"])->setBody("测试swift mail");

        $emailHandler = new \Monolog\Handler\SwiftMailerHandler($mailer, $messager);

        $errHandler->pushProcessor(function ($record) {
            $record['extra']['time'] = time();
            return $record;
        });
        $errHandler->setFormatter(new \Monolog\Formatter\LineFormatter());
        $infoHandler->setFormatter(new \Monolog\Formatter\HtmlFormatter("Y-m-d H:i:s"));

        $logger->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
        $logger->pushProcessor();

        //$logger->pushHandler($debugHandler);
        //$logger->pushHandler($infoHandler);
        //$logger->pushHandler($errHandler);
        $logger->pushHandler($emailHandler);

        echo "--2--:".microtime(true) ."\n";
        $logger->error("测试 error", ['error' => 'debug error']);
        echo "--3--:".microtime(true) ."\n";
    });
    echo "--4--:".microtime(true) ."\n";
});
$server->on('Receive', function ($server) {
    echo "receive-";
});

$server->start();


