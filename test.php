<?php

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

class A
{
    public function say()
    {
        throw new \Weiche\Scheduler\Exception\RunException("run error");
    }

    public function hello()
    {
        try {
            $this->say();
        } catch (\Exception $e) {
            print_r($e);
        }
    }
}

(new A)->hello();