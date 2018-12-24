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

$logger = new Logger("test");

$infoHandler = new StreamHandler(DATA_PATH . '/log/info.log', Logger::INFO);
$infoHandler->setBubble(false);
$debugHandler = new StreamHandler(STDOUT, Logger::DEBUG);
$debugHandler->setBubble(false);
$errHandler = new StreamHandler(DATA_PATH . '/log/error.log', Logger::ERROR);
$errHandler->setBubble(false);

$emailHandler = new \Monolog\Handler\NativeMailerHandler("songlin.zhang@weicheche.cn", "测试", "xiong.luo@weicheche.cn");
//$emailHandler2 = new \Monolog\Handler\SwiftMailerHandler();

$errHandler->pushProcessor(function ($record) {
    $record['extra']['time'] = time();
    return $record;
});
$errHandler->setFormatter(new \Monolog\Formatter\LineFormatter());
$infoHandler->setFormatter(new \Monolog\Formatter\HtmlFormatter("Y-m-d H:i:s"));

$logger->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());

//$logger->pushHandler($debugHandler);
//$logger->pushHandler($infoHandler);
//$logger->pushHandler($errHandler);
$logger->pushHandler($emailHandler);

$logger->error("测试 error", ['error' => 'debug error']);

