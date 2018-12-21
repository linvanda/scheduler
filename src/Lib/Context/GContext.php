<?php

/**
 * 整个服务全局上下文，该上下文中的变量全都是共享内存
 * Class GContext
 */
class GContext
{
    private static $instance;

    private function __construct()
    {

    }

    public static function inst()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
