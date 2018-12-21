<?php

/**
 * 操作脚本（入口程序）
 */

use Scheduler\Container;

error_reporting(E_ERROR);

// PHP 版本检测
if (explode('.', phpversion())[0] < 7) {
    exit("需要 PHP 7.0 或以上版本\n");
}

// swoole 版本检测
if (explode('.', phpversion('swoole'))[0] < 4) {
    exit("需要 Swoole 4.0 以上版本，建议 4.2.9 以上\n");
}

// 需禁用 xdebug
if (phpversion('xdebug')) {
    exit("请关闭 xdebug\n");
}

// 常量定义
define('ROOT_PATH', dirname(__FILE__));
define('APP_PATH', ROOT_PATH . '/src/App');
define('CONFIG_PATH', APP_PATH . '/Config');
define('DATA_PATH', ROOT_PATH . '/data');
define('ENV_DEV', 'dev');
define('ENV_TEST', 'test');
define('ENV_PREVIEW', 'preview');
define('ENV_PRODUCTION', 'production');

$cliOpts = getopt('f', ['start', 'stop', 'restart', 'reload', 'status', 'help', 'debug', 'env:', 'force']);
list($operation, $env, $debug, $forceStop) = extractOpt($cliOpts);

if (!$operation) {
    exit("请指定操作，如需帮助请键入 --help\n");
}

switch ($operation) {
    case 'start':
        start($env, $debug);
        break;
    case 'stop':
        stop($forceStop);
        break;
    case 'restart':
        restart($forceStop);
        break;
    case 'reload':
        reload();
        break;
    case 'status':
        status();
        break;
    case 'help':
        help();
        break;
}

/**
 * 启动服务
 * @param $env
 * @param bool $debug
 * @return bool
 */
function start($env, $debug = false)
{
    if (!in_array($env, [ENV_DEV, ENV_TEST, ENV_PREVIEW, ENV_PRODUCTION])) {
        echo "请指定环境\n";
        return false;
    }

    echo "启动服务...\n";

    defined('define') or define('ENV', $env);
    require_once(ROOT_PATH . '/vendor/autoload.php');

    try {
        Container::make('Server', ['debug' => $debug])->start();
    } catch (\Exception $e) {
        echo "程序执行异常：" . print_r($e->getMessage(), true) . "\n";
        return false;
    }

    return true;
}

/**
 * 停止服务
 * @param bool $force 是否强制停止，强制停止是 kill -9 信号，否则是平滑重启 kill -15
 * @return bool
 */
function stop($force = false)
{
    if (!isServerRunning()) {
        echo "服务未启动\n";
        return true;
    }

    echo ($force ? "强制" : "") . "停止服务...\n";

    $masterPid = masterPid();

    if (!$force) {
        if ($noExits = kill($masterPid)) {
            echo "进程" . implode(',', $noExits) . "未成功退出，请重试，或者强制退出\n";
            return false;
        }
    } else {
        // 强制 kill
        $managerPid = managerPid($masterPid);
        $workerPids = workerPids($managerPid);

        echo "kill 管理进程和主进程...\n";
        kill([$managerPid, $masterPid], true);

        echo "kill 子进程...\n";
        kill($workerPids, true);

        echo "删除 master.pid\n";
        unlink(DATA_PATH . '/master.pid');
    }

    return true;
}

/**
 * 重启，先 stop 然后 start
 * @param bool $force
 * @param null $env
 */
function restart($force = false)
{
    echo "重启...\n";

    if (stop($force)) {
        start('dev');
    }
}

/**
 * 在线重新加载
 */
function reload()
{
    if (!isServerRunning()) {
        echo "服务没有启动\n";
        return false;
    }

    echo "热重载...\n";

    $managerPid = managerPid(masterPid());
    $oldWorkerPids = workerPids($managerPid);
    swoole_process::kill($managerPid, SIGUSR1);

    sleep(1);

    $retry = 0;
    $suc = false;
    while ($retry++ < 5) {
        if (array_diff($oldWorkerPids, workerPids($managerPid))) {
            $suc = true;
            break;
        }
        sleep(3);
    }

    if ($suc) {
        echo "热重载成功\n";
        return true;
    } else {
        echo "热重载失败\n";
        return false;
    }
}

/**
 * 打印统计信息
 */
function status()
{
    $messages = [];
    $masterPid = masterPid();
    $managerPid = managerPid($masterPid);

    // 服务启动信息
    if (!$masterPid || !swoole_process::kill($masterPid, 0)) {
        echo "服务已停止\n";
        return;
    } else {
        $messages[] = "服务运行中。master pid: $masterPid, manager pid: $managerPid";
    }

    // 子进程信息
    $workers = workerPids($managerPid);
    $messages[] = "worker 进程数：" . count($workers) . "。worker 进程 pid：" . implode(',', $workers);

    //TODO 获取运行时的统计信息

    foreach ($messages as $msg) {
        echo "- $msg\n";
    }
}

/**
 * 根据 manager pid 获取 worker pid 数组
 * @param $managerPid
 * @return mixed
 */
function workerPids($managerPid)
{
    exec("ps -ef|grep $managerPid|awk '$3==$managerPid{print $2}'", $workers);
    return $workers;
}

function managerPid($masterPid)
{
    if (!$masterPid || !swoole_process::kill($masterPid, 0)) {
        return 0;
    }

    exec("ps -ef|grep $masterPid|awk '$3==$masterPid{print $2}'", $manger);
    return $manger[0];
}

function masterPid()
{
    return file_get_contents(DATA_PATH . '/master.pid');
}

function isServerRunning()
{
    if (!($masterPid = masterPid()) || !swoole_process::kill($masterPid, 0)) {
        return false;
    }

    return true;
}

/**
 * 退出进程
 * @param int|array $pids
 * @param bool $force
 * @param int $maxRetry
 * @return array 未成功退出的进程列表
 */
function kill($pids, $force = false, $maxRetry = 3)
{
    $sign = $force ? SIGKILL : SIGTERM;
    $pids = is_array($pids) ? $pids : [$pids];

    $retry = 0;
    do {
        foreach ($pids as $pid) {
            swoole_process::kill($pid, $sign);
        }

        sleep(1);

        foreach ($pids as $k => $pid) {
            if (!swoole_process::kill($pid, 0)) {
                unset($pids[$k]);
            }
        }

        if (!$pids) {
            break;
        }
    } while ($retry++ < $maxRetry);

    // 返回未被成功关闭的进程列表，如果全部退出，则返回空数组
    return $pids;
}

function help()
{
    echo <<<HELP
启动服务:         php scheduler.php --start --env=production
调试模式启动服务：php scheduler.php --start --env=production --debug (前台运行，且会记录详细日志信息)
停止服务：        php scheduler.php --stop (发送 SIGTERM 信号)
强制停止服务：    php scheduler.php --stop -f (或 --force。发送 SIGKILL 信号，除非无法正常停止，否则不要用这个，可能会丢失数据)
重启服务：        php scheduler.php --restart （先 stop 再 start，加 -f 或 --force 表示强制重启）
热重载服务：      php scheduler.php --reload （master 进程不重启，只重启 worker 进程）
查看服务状态：    php scheduler.php --status
查看帮助信息：    php scheduler.php --help

HELP;

}

/**
 * 解析命令行参数
 * @param $cliOpts
 * @return array
 */
function extractOpt($cliOpts)
{
    $operation = '';
    $env = '';
    $debug = false;
    $forceStop = false;
    foreach ($cliOpts as $cmd => $val) {
        switch ($cmd) {
            case 'start':
            case 'stop':
            case 'restart':
            case 'reload':
            case 'status':
            case 'help':
                $operation = $cmd;
                break;
            case 'f':
            case 'force':
                $forceStop = true;
                break;
            case 'debug':
                $debug = true;
                break;
            case 'env':
                $env = strtolower($val);
                break;
        }
    }

    return [$operation, $env, $debug, $forceStop];
}
