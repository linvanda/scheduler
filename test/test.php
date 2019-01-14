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

function f(Swoole\Coroutine\MySQL $mysql)
{
    $st = $mysql->prepare('select * from wei_users where uid=?');
    $res = $st->execute([102]);

    return $res;
}



class A
{
    private $name;
    public $age;

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }

    public function f()
    {
        return $this->age = 452;
    }

    public function c()
    {
        return$this->age;
    }
}

$n = new A("san");

echo $n->f();
echo "==".$n->c();
exit;





co::create(function () {
   $mysql = new \Scheduler\Fundation\MySQL\CoConnector('192.168.85.135', 'root', 'weicheche', 'weicheche');
   $res2 = $mysql->query("select `begin` from wei_sl_test");
   var_export($res2);



//    echo "err:{$mysql->error};result:".print_r($res, true)."\n";
});


