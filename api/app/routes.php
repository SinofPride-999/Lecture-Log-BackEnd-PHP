<?php

use App\Controllers\HomeController;

// Simple home route for testing
$router->get('/', function($request) {
    $controller = new HomeController($request);
    $controller->index();
});

// API routes will be added in Phase 2
$router->group(function($router) {
    // Health check endpoint
    $router->get('/api/health', function($request) {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'healthy',
            'timestamp' => time(),
            'environment' => $_ENV['APP_ENV'] ?? 'unknown'
        ]);
    });

    // Test database connection
    $router->get('/api/test-db', function($request) {
        try {
            $db = \App\Models\Database::getConnection();
            $stmt = $db->query("SELECT 1 as test");
            $result = $stmt->fetch();

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Database connection successful',
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ]);
        }
    });
});
