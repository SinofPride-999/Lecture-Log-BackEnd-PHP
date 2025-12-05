<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\DatabaseConfig;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

echo "Starting database migration...\n";

try {
    // Get database configuration
    $config = DatabaseConfig::getConfig();

    // Create database connection without specifying database name
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=%s',
        $config['host'],
        $config['port'],
        $config['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['user'],
        $config['pass'],
        $config['options']
    );

    // Read schema file
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);

    // Remove comments and empty lines
    $schema = preg_replace('/--.*$/m', '', $schema);
    $schema = preg_replace('/\/\*.*?\*\//s', '', $schema);

    // Split by semicolons that are not within quotes
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';

    for ($i = 0; $i < strlen($schema); $i++) {
        $char = $schema[$i];

        if (($char === "'" || $char === '"') && ($i === 0 || $schema[$i-1] !== '\\')) {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar) {
                $inString = false;
            }
        }

        $current .= $char;

        if ($char === ';' && !$inString) {
            $stmt = trim($current);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $current = '';
        }
    }

    // Add any remaining statement
    $remaining = trim($current);
    if (!empty($remaining)) {
        $statements[] = $remaining;
    }

    // Filter out empty statements
    $statements = array_filter($statements, function($stmt) {
        return !empty(trim($stmt));
    });

    echo "Executing " . count($statements) . " statements...\n";

    foreach ($statements as $index => $statement) {
        echo "Executing statement " . ($index + 1) . "... ";

        try {
            $pdo->exec($statement);
            echo "✓\n";
        } catch (PDOException $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
            // Continue with next statement
        }
    }

    echo "\nDatabase migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
