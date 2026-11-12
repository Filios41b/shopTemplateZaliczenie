
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

ini_set('display_errors', '0');
error_reporting(E_ALL);

register_shutdown_function(function () {
    $error = error_get_last();

    if ($error !== null) {
        error_log('LARAVEL SHUTDOWN ERROR: ' . json_encode($error));
    } else {
        error_log('LARAVEL SHUTDOWN: bez bledu krytycznego');
    }
});

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

error_log('LARAVEL: przed handleRequest');

try {
    $app->handleRequest(Request::capture());

    error_log('LARAVEL: po handleRequest');
} catch (Throwable $e) {
    error_log('LARAVEL EXCEPTION: ' . get_class($e));
    error_log('LARAVEL MESSAGE: ' . $e->getMessage());
    error_log('LARAVEL FILE: ' . $e->getFile() . ':' . $e->getLine());
}
