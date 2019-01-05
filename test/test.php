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
   $mysql = new Swoole\Coroutine\MySQL();
   $conn = $mysql->connect([
       'host' => '192.168.85.135',
       'port' => 3306,
       'user' => 'root',
       'password' => 'weicheche',
       'database' => 'weicheche',
       'timeout' => 3,
       'charset' => 'utf8',
   ]);

   if (!$conn) {
       throw new \Exception('mysql连接出错：'.$mysql->connect_errno);
   }

    $result = $mysql->query("select * from wei_sl_test");
   echo "jieguo:";
   var_export($result);
   echo $mysql->affected_rows;
});


