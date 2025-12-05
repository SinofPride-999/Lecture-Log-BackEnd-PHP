<?php

// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Request;
use App\Core\Router;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} else {
    // Create minimal .env if doesn't exist
    file_put_contents(__DIR__ . '/../.env', "APP_ENV=development\n");
}

// Error reporting based on environment
if ($_ENV['APP_ENV'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set('UTC');

// Create request and router
$request = new Request();
$router = new Router($request);

// Define routes
require_once __DIR__ . '/../app/routes.php';

// Dispatch the request
try {
    $router->dispatch();
} catch (Throwable $e) {
    // Handle uncaught exceptions
    http_response_code(500);
    header('Content-Type: application/json');

    $errorData = [
        'success' => false,
        'message' => 'Internal server error',
        'timestamp' => time()
    ];

    if ($_ENV['APP_ENV'] === 'development') {
        $errorData['error'] = $e->getMessage();
        $errorData['file'] = $e->getFile();
        $errorData['line'] = $e->getLine();
        $errorData['trace'] = $e->getTrace();
    }

    echo json_encode($errorData, JSON_PRETTY_PRINT);
}
