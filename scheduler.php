<?php

/**
 * 入口程序
 */

define('ROOT_PATH', dirname(__FILE__) . '/');
define('APP_PATH', ROOT_PATH . 'src/');

require(ROOT_PATH . 'vendor/autoload.php');

$server = new \Weiche\Scheduler\Server();
