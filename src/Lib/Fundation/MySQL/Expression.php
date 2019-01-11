<?php

namespace Scheduler\Fundation\MySQL;

/**
 * 表达式
 * Class Expression
 * @package Scheduler\Fundation\MySQL
 */
class Expression
{
    private $value;

    public function __construct(string $exp)
    {
        $this->value = $exp;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
