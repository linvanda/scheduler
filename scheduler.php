<?php

use \Weiche\Scheduler\Server;
use \Weiche\Scheduler\Utils\Config;

/**
 * 命令行执行的入口程序
 */

error_reporting(E_ALL ^ E_NOTICE);

// 常量定义
define('ROOT_PATH', dirname(__FILE__));
define('APP_PATH', ROOT_PATH . '/src');
define('DATA_PATH', ROOT_PATH . '/data');
define('ENV_DEV', 'dev');
define('ENV_TEST', 'test');
define('ENV_PREVIEW', 'preview');
define('ENV_PRODUCTION', 'production');

require(ROOT_PATH . '/vendor/autoload.php');

// 命令行传参
$cliOpts = getopt('', ['debug', 'env:']);

if (!($env = $cliOpts['env']) || !in_array(($env = strtolower($env)), [ENV_DEV, ENV_TEST, ENV_PREVIEW, ENV_PRODUCTION])) {
    echo "请指定环境";
    exit(1);
}

define('ENV', $env);

(new Server(isset($cliOpts['debug']) ? 1 : 0))->start();
