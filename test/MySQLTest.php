<?php

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

go(function () {
    // 实际使用中此处采用服务容器提供 Query 对象
    $pool = \Scheduler\Fundation\MySQL\CoPool::instance(5);
    $transaction = new \Scheduler\Fundation\MySQL\Transaction($pool);
    $query = new \Scheduler\Fundation\MySQL\Query($transaction);

    $result = $query->select(['phone', 'uid'])->from('wei_users')->where(['uid'=>93])->one();
    var_export($result);
});

