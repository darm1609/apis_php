<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita4d6fd40585e5531ef197b69afe80d8d
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita4d6fd40585e5531ef197b69afe80d8d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita4d6fd40585e5531ef197b69afe80d8d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita4d6fd40585e5531ef197b69afe80d8d::$classMap;

        }, null, ClassLoader::class);
    }
}
