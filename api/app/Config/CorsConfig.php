<?php

namespace App\Config;

class CorsConfig
{
    public static function getConfig(): array
    {
        $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:3000');

        return [
            'allowed_origins' => $allowedOrigins,
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
            'allowed_headers' => [
                'Content-Type',
                'Authorization',
                'X-Requested-With',
                'Accept',
                'Origin'
            ],
            'exposed_headers' => [],
            'max_age' => 86400, // 24 hours
            'supports_credentials' => false,
        ];
    }
}
