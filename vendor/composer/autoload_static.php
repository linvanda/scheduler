<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc574d965ab6ccadf27444802ddafc77b
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Weiche\\Scheduler\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Weiche\\Scheduler\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc574d965ab6ccadf27444802ddafc77b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc574d965ab6ccadf27444802ddafc77b::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
