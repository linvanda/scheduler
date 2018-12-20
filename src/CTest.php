<?php

namespace Weiche\Scheduler;

class CTest
{
    protected $num = 0;
    protected $name;

    public function __construct($num, $name)
    {
        $this->num = $num;
        $this->name = $name;
    }

    public function add()
    {
        $this->num++;
    }

    public function get()
    {
        return 'ctest:'.$this->num . '==' . $this->name;
    }
}
