<?php

namespace App\Config;

class DatabaseConfig
{
    public static function getConfig(): array
    {
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'name' => $_ENV['DB_NAME'] ?? 'lecture_log',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'pass' => $_ENV['DB_PASS'] ?? '',
            'charset' => 'utf8mb4',
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_PERSISTENT => true, // Connection pooling
            ]
        ];
    }
}
