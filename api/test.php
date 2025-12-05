<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "Testing autoload...\n";

// Test if classes can be loaded
try {
    $test1 = new App\Config\DatabaseConfig();
    echo "✅ DatabaseConfig loaded\n";

    $test2 = new App\Models\Database();
    echo "✅ Database model loaded\n";

    echo "\nAll classes loaded successfully!\n";
} catch (Error $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
