<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Mengalihkan folder temp bawaan PHP ke storage aplikasi sendiri
// Sangat penting untuk hosting gratisan (InfinityFree) yang /tmp-nya sering penuh/diblokir
$customTmpDir = __DIR__.'/../storage/app/tmp';
if (!is_dir($customTmpDir)) {
    @mkdir($customTmpDir, 0777, true);
}
putenv('TMPDIR=' . $customTmpDir);

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Paksa muat bypass tmpfile Livewire karena vendor/autoload.php di server gratisan mungkin tidak terupdate
if (file_exists(__DIR__.'/../app/Helpers/LivewireTmpfileBypass.php')) {
    require_once __DIR__.'/../app/Helpers/LivewireTmpfileBypass.php';
}
// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
