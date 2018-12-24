<?php

namespace App\Controller;

use Scheduler\Controller;
use Scheduler\Infrastructure\Response\Response;

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
        return ['code' => Response::CODE_DELAY];
    }

    public function step2()
    {
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
