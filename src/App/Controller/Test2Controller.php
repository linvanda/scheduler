<?php

namespace App\Controller;

use Scheduler\Controller;

/**
 * 测试处理程序
 *
 * Class Test2Controller
 * @package App\Controller
 */
class Test2Controller extends Controller
{
    public function foo()
    {
        echo "foo\n";
        sleep(1);
        exit;
        return true;
    }

    public function bar()
    {
        echo "bar\n";
        sleep(5);

        return true;
    }
}
