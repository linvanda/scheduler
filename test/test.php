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
//       'fetch_mode' => true,
   ]);

    $res = $mysql->prepare("insert into wei_sl_test2(id,nickname) values(?,?)");
    $res = $res->execute([md5(time()), '"ceshi\'']);

    echo "err:{$mysql->insert_id};result:".print_r($res, true)."\n";
});

//
//$servername = "192.168.85.135";
//$username = "root";
//$password = "weicheche";
//$dbname = "weicheche";
//
//// 创建链接
//$conn = new mysqli($servername, $username, $password, $dbname);
//// 检查链接
//if ($conn->connect_error) {
//    die("连接失败: " . $conn->connect_error);
//}
//
//$sql = "insert into wei_sl_test(nickname) values('阿法===r');insert into wei_sl_test(nickname) values('阿法===r');select * from  wei_sl_test";
//
//if ($conn->multi_query($sql) === TRUE) {
//    echo "新记录插入成功";
//} else {
//    echo "Error: " . $sql . "<br>" . $conn->error;
//}
//
//$conn->close();