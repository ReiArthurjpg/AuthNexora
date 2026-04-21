<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
$env = require __DIR__ . '/src/Config/env.php';

use App\Services\GoogleAuthService;
use App\Config\Database;

try {
    echo "Testing Database Connection...\n";
    Database::connection();
    echo "DB SUCCESS\n";

    echo "Testing Google Auth Service...\n";
    $googleAuth = new GoogleAuthService($env['google']);
    $url = $googleAuth->getAuthUrl();
    echo "URL: " . $url . "\n";
    echo "SUCCESS\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
