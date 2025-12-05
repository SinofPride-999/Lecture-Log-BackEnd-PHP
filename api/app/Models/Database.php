<?php

namespace App\Models;

use App\Config\DatabaseConfig;
use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    /**
     * Get database connection (singleton)
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::createConnection();
        }

        return self::$connection;
    }

    /**
     * Create new database connection
     */
    private static function createConnection(): void
    {
        $config = DatabaseConfig::getConfig();

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        try {
            self::$connection = new PDO(
                $dsn,
                $config['user'],
                $config['pass'],
                $config['options']
            );

            // Set timezone to UTC
            self::$connection->exec("SET time_zone = '+00:00'");

        } catch (PDOException $e) {
            throw new \RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * Close database connection
     */
    public static function closeConnection(): void
    {
        self::$connection = null;
    }

    /**
     * Execute a transaction
     */
    public static function transaction(callable $callback): mixed
    {
        $db = self::getConnection();

        try {
            $db->beginTransaction();
            $result = $callback($db);
            $db->commit();
            return $result;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
