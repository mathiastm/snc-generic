<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitafc3ed4feb7f527cbfe851dd6514ea68
{
    public static $prefixLengthsPsr4 = array (
        'Z' => 
        array (
            'Zend\\View\\' => 10,
            'Zend\\Validator\\' => 15,
            'Zend\\Uri\\' => 9,
            'Zend\\Stdlib\\' => 12,
            'Zend\\SkeletonInstaller\\' => 23,
            'Zend\\ServiceManager\\' => 20,
            'Zend\\Router\\' => 12,
            'Zend\\Mvc\\' => 9,
            'Zend\\ModuleManager\\' => 19,
            'Zend\\Loader\\' => 12,
            'Zend\\Http\\' => 10,
            'Zend\\EventManager\\' => 18,
            'Zend\\Escaper\\' => 13,
            'Zend\\Config\\' => 12,
            'Zend\\ComponentInstaller\\' => 24,
            'ZF\\DevelopmentMode\\' => 19,
        ),
        'I' => 
        array (
            'Interop\\Container\\' => 18,
        ),
        'A' => 
        array (
            'Application\\' => 12,
            'ApplicationTest\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Zend\\View\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-view/src',
        ),
        'Zend\\Validator\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-validator/src',
        ),
        'Zend\\Uri\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-uri/src',
        ),
        'Zend\\Stdlib\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-stdlib/src',
        ),
        'Zend\\SkeletonInstaller\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-skeleton-installer/src',
        ),
        'Zend\\ServiceManager\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-servicemanager/src',
        ),
        'Zend\\Router\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-router/src',
        ),
        'Zend\\Mvc\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-mvc/src',
        ),
        'Zend\\ModuleManager\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-modulemanager/src',
        ),
        'Zend\\Loader\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-loader/src',
        ),
        'Zend\\Http\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-http/src',
        ),
        'Zend\\EventManager\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-eventmanager/src',
        ),
        'Zend\\Escaper\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-escaper/src',
        ),
        'Zend\\Config\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-config/src',
        ),
        'Zend\\ComponentInstaller\\' => 
        array (
            0 => __DIR__ . '/..' . '/zendframework/zend-component-installer/src',
        ),
        'ZF\\DevelopmentMode\\' => 
        array (
            0 => __DIR__ . '/..' . '/zfcampus/zf-development-mode/src',
        ),
        'Interop\\Container\\' => 
        array (
            0 => __DIR__ . '/..' . '/container-interop/container-interop/src/Interop/Container',
        ),
        'Application\\' => 
        array (
            0 => __DIR__ . '/../..' . '/module/Application/src',
        ),
        'ApplicationTest\\' => 
        array (
            0 => __DIR__ . '/../..' . '/module/Application/test',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitafc3ed4feb7f527cbfe851dd6514ea68::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitafc3ed4feb7f527cbfe851dd6514ea68::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
