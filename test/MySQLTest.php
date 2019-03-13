<?php

/**
 * 命令行执行的入口程序
 */

use \Swoole\Coroutine as co;
use Scheduler\Context\GContext;
use Scheduler\Fundation\LoggerCollector;
use Scheduler\Fundation\Container;

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
define('LOG_LEVEL', 'debug');

define('ENV', 'dev');
require(ROOT_PATH . '/vendor/autoload.php');

// 初始化一些依赖项
Container::init();
GContext::init();
swoole_timer_tick(50, new LoggerCollector());

// 实际使用中此处采用服务容器提供 Query 对象
$pool = \Scheduler\Fundation\MySQL\CoPool::instance(3); // 连接池大小为3
$transaction = new \Scheduler\Fundation\MySQL\Transaction($pool);
$query = new \Scheduler\Fundation\MySQL\Query($transaction);

/**
 * 模拟多协程并发查询，并观察连接池使用情况
 * 模拟的场景是：每0.4s有一个客户端连接进入（新的协程）并触发
 */
go(function () use ($query, $pool) {
    // 模拟10次查询，每次耗时3s
    for ($i = 0; $i < 6; $i++) {
        go(function () use ($query, $pool) {
            $result = $query->execute('select sleep(3)');
            echo "query done\n";
            var_export($pool->count());
        });
        co::sleep(0.4);
    }

    co::sleep(5);// 此时前面三个连接对象都应该push到连接池了，下面进行的4次连接应该只需要创建一个新的

    for ($i = 0; $i < 4; $i++) {
        go(function () use ($query, $pool) {
            $result = $query->execute('select sleep(1)');
            echo "query suc\n";
            var_export($pool->count());
        });
    }
});

// 实际业务演示
//go(function () {
//    $pool = \Scheduler\Fundation\MySQL\CoPool::instance(3); // 连接池大小为3
//    $transaction = new \Scheduler\Fundation\MySQL\Transaction($pool);
//    $query = new \Scheduler\Fundation\MySQL\Query($transaction);
//
//    // 模拟10次查询
//    for ($i = 0; $i < 6; $i++) {
//        go(function () use ($query) {
//            $result = $query->select(['phone', 'username'])->from('wei_users')->where(['uid' => 93])->one();
//            var_export($result);
//        });
//        co::sleep(0.5);
//    }
//
//    co::sleep(3);
//
//    for ($i = 0; $i < 4; $i++) {
//        go(function () use ($query) {
//            $query->insert('wei_sl_test')->values(['nickname' => '山子', 'name' => '张山'])->execute();
//        });
//    }
//});

// 事务
//go(function () {
//    $pool = \Scheduler\Fundation\MySQL\CoPool::instance(3); // 连接池大小为3
//    $transaction = new \Scheduler\Fundation\MySQL\Transaction($pool);
//    $query = new \Scheduler\Fundation\MySQL\Query($transaction);
//
//    $query->begin();
//    $query->insert('wei_sl_test')->values(['nickname' => '山子1', 'name' => '张山'])->execute();
//    $query->insert('wei_sl_test')->values(['nickname' => '山子2', 'name' => '张山'])->execute();
//    $query->insert('wei_sl_test')->values(['nickname' => '山子3', 'name' => '张山'])->execute();
//    $query->commit();
//
//    var_export($query->select('*')->from('wei_sl_test')->orderBy('id desc')->limit(10)->list());
//});
