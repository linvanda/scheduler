<?php

namespace App\Controller;

use Scheduler\Controller;
use Swoole\Coroutine as co;

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
        echo "step1\n";
        co::sleep(1);
        echo "step1 - 1\n";

        return true;
    }

    public function step2()
    {
        echo "step2\n";
        co::sleep(2);
        echo "step2 - 2\n";

        return true;
    }

    public function step3()
    {
        echo "step3\n";
        co::sleep(3);
        echo "step3 - 3\n";

        return true;
    }

    public function step4()
    {
        echo "step4\n";
        co::sleep(1);
        echo "step4 - 4\n";
        return true;
    }

    public function step5()
    {
        echo "step5\n";
        co::sleep(3);
        echo "step5 - 5\n";
        return true;
    }

    public function step6()
    {
        echo "step6\n";
        return true;
    }

    public function step7()
    {
        echo "step7\n";
        return true;
    }
}
