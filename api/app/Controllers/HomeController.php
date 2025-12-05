<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->success('Attendance System API', [
            'name' => 'Attendance System API',
            'version' => '1.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'development',
            'endpoints' => [
                'GET /api/health' => 'Health check',
                'GET /api/test-db' => 'Test database connection'
            ],
            'documentation' => 'Coming soon...'
        ]);
    }
}
