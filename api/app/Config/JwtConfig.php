<?php

namespace App\Config;

class JwtConfig
{
    public static function getConfig(): array
    {
        return [
            'secret' => $_ENV['JWT_SECRET'] ?? 'default-secret-key',
            'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
            'expire_hours' => (int)($_ENV['JWT_EXPIRE_HOURS'] ?? 24),
        ];
    }
}
