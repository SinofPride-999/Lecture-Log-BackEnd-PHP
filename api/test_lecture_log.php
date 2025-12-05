<?php

echo "Testing lecture_log database...\n";

$host = 'localhost';
$user = 'root';
$pass = 'darkplace'; // Your MySQL password
$dbname = 'lecture_log';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname}", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to: {$dbname}\n\n";
    
    // Check counts
    $tables = ['students', 'professors', 'courses', 'sessions', 'student_courses', 'attendance'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
        $count = $stmt->fetch()['count'];
        echo "{$table}: {$count} records\n";
    }
    
    // Show students with enrollments
    echo "\n=== STUDENTS WITH ENROLLMENTS ===\n";
    $stmt = $pdo->query("
        SELECT s.id, s.student_id, s.name, 
               GROUP_CONCAT(c.course_code ORDER BY c.course_code) as courses,
               COUNT(sc.course_id) as course_count
        FROM students s
        LEFT JOIN student_courses sc ON s.id = sc.student_id
        LEFT JOIN courses c ON sc.course_id = c.id
        GROUP BY s.id
        ORDER BY s.name
    ");
    
    $students = $stmt->fetchAll();
    
    if (empty($students)) {
        echo "No students found!\n";
    } else {
        foreach ($students as $student) {
            echo "{$student['name']} ({$student['student_id']}): ";
            echo $student['course_count'] > 0 ? "{$student['courses']} ({$student['course_count']} courses)" : "No courses";
            echo "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
