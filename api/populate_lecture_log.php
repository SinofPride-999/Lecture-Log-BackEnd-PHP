<?php

echo "Populating lecture_log database...\n";

$host = 'localhost';
$user = 'root';
$pass = 'darkplace';
$dbname = 'lecture_log';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname}", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to: {$dbname}\n\n";
    
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $studentCount = $stmt->fetch()['count'];
    
    if ($studentCount > 0) {
        echo "⚠️  Database already has {$studentCount} students.\n";
        echo "Run 'php reset_lecture_log.php' to clear and re-populate.\n";
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    echo "1. Inserting students...\n";
    $students = [
        ['student_id' => 'S001', 'name' => 'John Doe', 'email' => 'john.doe@university.edu', 'is_rep' => 1, 'is_approved' => 1],
        ['student_id' => 'S002', 'name' => 'Jane Smith', 'email' => 'jane.smith@university.edu', 'is_rep' => 0, 'is_approved' => 1],
        ['student_id' => 'S003', 'name' => 'Bob Johnson', 'email' => 'bob.johnson@university.edu', 'is_rep' => 1, 'is_approved' => 1],
        ['student_id' => 'S004', 'name' => 'Alice Brown', 'email' => 'alice.brown@university.edu', 'is_rep' => 0, 'is_approved' => 1],
    ];
    
    $studentIds = [];
    foreach ($students as $student) {
        $stmt = $pdo->prepare("
            INSERT INTO students (student_id, name, email, is_rep, is_approved) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(array_values($student));
        $studentIds[$student['student_id']] = $pdo->lastInsertId();
        echo "  ✓ {$student['name']} ({$student['student_id']})\n";
    }
    
    echo "\n2. Inserting professors...\n";
    $professors = [
        ['professor_id' => 'P001', 'name' => 'Dr. Michael Chen', 'email' => 'mchen@university.edu', 'department' => 'Computer Science'],
        ['professor_id' => 'P002', 'name' => 'Dr. Sarah Wilson', 'email' => 'swilson@university.edu', 'department' => 'Mathematics'],
        ['professor_id' => 'P003', 'name' => 'Dr. Robert Davis', 'email' => 'rdavis@university.edu', 'department' => 'Engineering'],
    ];
    
    $professorIds = [];
    foreach ($professors as $professor) {
        $stmt = $pdo->prepare("
            INSERT INTO professors (professor_id, name, email, department) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute(array_values($professor));
        $professorIds[$professor['professor_id']] = $pdo->lastInsertId();
        echo "  ✓ {$professor['name']} ({$professor['professor_id']})\n";
    }
    
    echo "\n3. Inserting courses...\n";
    $courses = [
        ['course_code' => 'CS101', 'course_name' => 'Introduction to Computer Science', 'semester' => 'Fall 2025', 'credits' => 3, 'is_active' => 1],
        ['course_code' => 'MATH201', 'course_name' => 'Calculus II', 'semester' => 'Fall 2025', 'credits' => 4, 'is_active' => 1],
        ['course_code' => 'ENG301', 'course_name' => 'Advanced Engineering', 'semester' => 'Fall 2025', 'credits' => 3, 'is_active' => 1],
        ['course_code' => 'CS202', 'course_name' => 'Data Structures', 'semester' => 'Spring 2026', 'credits' => 3, 'is_active' => 1],
    ];
    
    $courseIds = [];
    foreach ($courses as $course) {
        $stmt = $pdo->prepare("
            INSERT INTO courses (course_code, course_name, semester, credits, is_active) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(array_values($course));
        $courseIds[$course['course_code']] = $pdo->lastInsertId();
        echo "  ✓ {$course['course_code']} - {$course['course_name']}\n";
    }
    
    echo "\n4. Assigning professors to courses...\n";
    $assignments = [
        [$professorIds['P001'], $courseIds['CS101'], 'instructor'],
        [$professorIds['P002'], $courseIds['MATH201'], 'instructor'],
        [$professorIds['P003'], $courseIds['ENG301'], 'instructor'],
        [$professorIds['P001'], $courseIds['CS202'], 'instructor'],
    ];
    
    foreach ($assignments as [$profId, $courseId, $role]) {
        $stmt = $pdo->prepare("
            INSERT INTO professor_courses (professor_id, course_id, role, assigned_date) 
            VALUES (?, ?, ?, CURDATE())
        ");
        $stmt->execute([$profId, $courseId, $role]);
        echo "  ✓ Assigned professor to course\n";
    }
    
    echo "\n5. Enrolling students in courses...\n";
    $enrollments = [
        [$studentIds['S001'], $courseIds['CS101'], '2025-09-01'],
        [$studentIds['S001'], $courseIds['MATH201'], '2025-09-01'],
        [$studentIds['S002'], $courseIds['CS101'], '2025-09-01'],
        [$studentIds['S002'], $courseIds['ENG301'], '2025-09-01'],
        [$studentIds['S003'], $courseIds['MATH201'], '2025-09-01'],
        [$studentIds['S003'], $courseIds['ENG301'], '2025-09-01'],
        [$studentIds['S004'], $courseIds['CS101'], '2025-09-01'],
        [$studentIds['S004'], $courseIds['CS202'], '2025-09-01'],
    ];
    
    foreach ($enrollments as [$studentId, $courseId, $date]) {
        $stmt = $pdo->prepare("
            INSERT INTO student_courses (student_id, course_id, enrollment_date, status) 
            VALUES (?, ?, ?, 'enrolled')
        ");
        $stmt->execute([$studentId, $courseId, $date]);
        echo "  ✓ Enrolled student in course\n";
    }
    
    echo "\n6. Creating active sessions...\n";
    $now = date('Y-m-d H:i:s');
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $sessions = [
        [
            'course_id' => $courseIds['CS101'],
            'professor_id' => $professorIds['P001'],
            'qr_token' => bin2hex(random_bytes(32)),
            'title' => 'Lecture 1: Introduction',
            'session_date' => date('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'expires_at' => $expires,
            'is_active' => 1
        ],
        [
            'course_id' => $courseIds['MATH201'],
            'professor_id' => $professorIds['P002'],
            'qr_token' => bin2hex(random_bytes(32)),
            'title' => 'Calculus Review',
            'session_date' => date('Y-m-d'),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'expires_at' => $expires,
            'is_active' => 1
        ],
    ];
    
    foreach ($sessions as $session) {
        $stmt = $pdo->prepare("
            INSERT INTO sessions (course_id, professor_id, qr_token, title, session_date, start_time, end_time, expires_at, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute(array_values($session));
        echo "  ✓ Created session: {$session['title']}\n";
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n✅ Database populated successfully!\n";
    echo "   - 4 students added\n";
    echo "   - 3 professors added\n";
    echo "   - 4 courses added\n";
    echo "   - 8 student enrollments\n";
    echo "   - 2 active sessions created\n";
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
