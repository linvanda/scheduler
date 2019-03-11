<?php

/**
 * 命令行执行的入口程序
 */

use \Swoole\Coroutine as co;

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

// 实际使用中此处采用服务容器提供 Query 对象
$pool = \Scheduler\Fundation\MySQL\CoPool::instance(3);
$transaction = new \Scheduler\Fundation\MySQL\Transaction($pool);
$query = new \Scheduler\Fundation\MySQL\Query($transaction);

go(function () use ($query, $pool) {
    // 模拟10次查询，每次耗时3s
    for ($i = 0; $i < 10; $i++) {
        go(function () use ($query, $pool) {
            $result = $query->execute('select sleep(3)');
            echo "query done\n";
            var_export($pool->count());
        });
    }

//    $pool->close();
});

