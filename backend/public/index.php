
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Diagnostyka PHP
ini_set('log_errors', '1');
ini_set('display_errors', '0');
error_reporting(E_ALL);

error_log('TEST INDEX: PHP uruchomil index.php');

// Wykrywanie bledow krytycznych
register_shutdown_function(function () {
    $error = error_get_last();

    if ($error !== null) {
        error_log('TEST SHUTDOWN ERROR: ' . json_encode($error));
    } else {
        error_log('TEST SHUTDOWN: brak bledu krytycznego');
    }
});

// Tryb konserwacji Laravel
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoload Composer
require __DIR__.'/../vendor/autoload.php';

// Uruchomienie Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';

error_log('TEST LARAVEL: aplikacja zaladowana');

try {
    error_log('TEST LARAVEL: przed handleRequest');

    $app->handleRequest(Request::capture());

    error_log('TEST LARAVEL: po handleRequest');

} catch (Throwable $e) {
    error_log('TEST ERROR TYPE: ' . get_class($e));
    error_log('TEST ERROR MESSAGE: ' . $e->getMessage());
    error_log('TEST ERROR FILE: ' . $e->getFile() . ':' . $e->getLine());
}
