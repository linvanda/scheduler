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

co::set([
    'log_level' => SWOOLE_LOG_ERROR
]);

co::create(function () {
   $mysql = new \Scheduler\Fundation\MySQL\CoConnector('192.168.85.135', 'root', 'weicheche', 'weicheche');
//   $mysql->query("select sleep(8)");
   $res2 = $mysql->query("insert into wei_sl_test(nickname) values('talino')");
   var_export($mysql->insertId());
    $channel = new co\Channel(4);
    $channel->push($mysql);

});


