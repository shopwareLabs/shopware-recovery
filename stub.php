<?php

error_reporting(-1);

if (function_exists('ini_set')) {
    @ini_set('display_errors', 1);
    @ini_set('display_startup_errors', 1);
    @ini_set('opcache.enable_cli', '0');
}

if (!extension_loaded('Phar')) {
    die('The PHP Phar extension is not enabled.');
}

if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
    date_default_timezone_set('UTC');
}

if ('cli' === PHP_SAPI || !isset($_SERVER['REQUEST_URI'])) {
    Phar::mapPhar('shopware-recovery.phar');
    require 'phar://shopware-recovery.phar/bin/console';
} else {
    function rewrites()
    {
        return false;
    }

    Phar::webPhar(
        null,
        'public/index.php',
        null,
        array(
            'php' => Phar::PHP,
            'css' => 'text/css',
            'js' => 'application/x-javascript',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'json' => 'application/json'
        ),
        'rewrites'
    );
}

__HALT_COMPILER();