<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/api/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

echo "Testing database tables...\n";

try {
    $db = App\Models\Database::getConnection();

    // Get list of tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Found " . count($tables) . " tables:\n";

    foreach ($tables as $table) {
        echo "  - {$table}\n";

        // Count rows
        $countStmt = $db->query("SELECT COUNT(*) as count FROM `{$table}`");
        $count = $countStmt->fetch()['count'];
        echo "    Rows: {$count}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
