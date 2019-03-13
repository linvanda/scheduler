<?php

namespace App\Controller;

use Scheduler\Controller;
use Swoole\Coroutine as co;
use Scheduler\Fundation\Logger;

/**
 * 测试处理程序
 *
 * Class TestController
 * @package App\Controller
 */
class TestController extends Controller
{
    public function step1()
    {
        Logger::debug("action step1开始处理请求。需要执行1s");
        co::sleep(1);
        Logger::debug('action step1执行完成');

        return true;
    }

    public function step2()
    {
        Logger::debug("action step2开始处理请求。需要执行2s");
        co::sleep(2);
        Logger::debug('action step2处理完成，失败需要重试');

        return ['code' => 400, 'msg' => '失败需要重试'];
    }

    public function step3()
    {
        Logger::debug('action step3 开始处理请求，需要执行3s');
        co::sleep(3);
        Logger::debug('action step3 处理完成');

        return true;
    }

    public function step4()
    {
        Logger::debug('action step4开始处理请求，需要执行1s');
        co::sleep(1);
        Logger::debug('action step4处理完成');
        return true;
    }

    public function step5()
    {
        Logger::debug('action step5开始处理请求,需要执行3s');
        co::sleep(3);
        Logger::debug('action step5处理完成');
        return true;
    }
}
