<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd633e0f0ee4a6f0c97677c74296734a9
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Workerman\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/workerman',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd633e0f0ee4a6f0c97677c74296734a9::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd633e0f0ee4a6f0c97677c74296734a9::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
