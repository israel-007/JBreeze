<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit60253d2fdb0895efce37eeb4ef50f535
{
    public static $prefixLengthsPsr4 = array (
        'j' => 
        array (
            'json\\' => 5,
            'jbreezeExceptions\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'json\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/jbreeze',
        ),
        'jbreezeExceptions\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/errorhandler',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit60253d2fdb0895efce37eeb4ef50f535::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit60253d2fdb0895efce37eeb4ef50f535::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit60253d2fdb0895efce37eeb4ef50f535::$classMap;

        }, null, ClassLoader::class);
    }
}
