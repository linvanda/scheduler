<?php

namespace App\Controller;

use Scheduler\Controller;

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
        echo "step1";
        return true;
    }

    public function step2()
    {
        echo "step2";
        return true;
    }

    public function step3()
    {
        echo "step3";
        return true;
    }

    public function step4()
    {
        echo "step4";
        return true;
    }

    public function step5()
    {
        echo "step5";
        return true;
    }
}
