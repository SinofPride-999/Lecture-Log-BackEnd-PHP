<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables if .env exists in same directory
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    echo "Warning: .env file not found, using defaults\n";
}

echo "Testing student enrollments...\n";

try {
    $db = App\Models\Database::getConnection();

    // Check all students
    $stmt = $db->query("SELECT * FROM students");
    $students = $stmt->fetchAll();

    echo "Found " . count($students) . " students:\n";

    foreach ($students as $student) {
        echo "\nStudent: {$student['name']} (ID: {$student['id']}, Student ID: {$student['student_id']})\n";

        // Check enrollments
        $enrollStmt = $db->prepare("
            SELECT c.course_code, c.course_name
            FROM student_courses sc
            JOIN courses c ON sc.course_id = c.id
            WHERE sc.student_id = ? AND sc.status = 'enrolled'
        ");
        $enrollStmt->execute([$student['id']]);
        $enrollments = $enrollStmt->fetchAll();

        if (empty($enrollments)) {
            echo "  No enrollments found\n";
        } else {
            echo "  Enrolled in " . count($enrollments) . " courses:\n";
            foreach ($enrollments as $enrollment) {
                echo "  - {$enrollment['course_code']}: {$enrollment['course_name']}\n";
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
