<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Database;
use App\Models\StudentModel;
use App\Models\ProfessorModel;
use App\Models\CourseModel;
use App\Models\SessionModel;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

echo "Seeding test data...\n";

try {
    $db = Database::getConnection();

    // Start transaction
    $db->beginTransaction();

    echo "Creating students...\n";
    $studentModel = new StudentModel();

    $students = [
        ['student_id' => 'S001', 'name' => 'John Doe', 'email' => 'john.doe@university.edu', 'is_rep' => true, 'is_approved' => true],
        ['student_id' => 'S002', 'name' => 'Jane Smith', 'email' => 'jane.smith@university.edu', 'is_rep' => false, 'is_approved' => true],
        ['student_id' => 'S003', 'name' => 'Bob Johnson', 'email' => 'bob.johnson@university.edu', 'is_rep' => true, 'is_approved' => true],
        ['student_id' => 'S004', 'name' => 'Alice Brown', 'email' => 'alice.brown@university.edu', 'is_rep' => false, 'is_approved' => true],
    ];

    foreach ($students as $student) {
        $studentModel->create($student);
    }

    echo "Creating professors...\n";
    $professorModel = new ProfessorModel();

    $professors = [
        ['professor_id' => 'P001', 'name' => 'Dr. Michael Chen', 'email' => 'mchen@university.edu', 'department' => 'Computer Science'],
        ['professor_id' => 'P002', 'name' => 'Dr. Sarah Wilson', 'email' => 'swilson@university.edu', 'department' => 'Mathematics'],
        ['professor_id' => 'P003', 'name' => 'Dr. Robert Davis', 'email' => 'rdavis@university.edu', 'department' => 'Engineering'],
    ];

    foreach ($professors as $professor) {
        $professorModel->create($professor);
    }

    echo "Creating courses...\n";
    $courseModel = new CourseModel();

    $courses = [
        ['course_code' => 'CS101', 'course_name' => 'Introduction to Computer Science', 'semester' => 'Fall 2025', 'credits' => 3, 'is_active' => true],
        ['course_code' => 'MATH201', 'course_name' => 'Calculus II', 'semester' => 'Fall 2025', 'credits' => 4, 'is_active' => true],
        ['course_code' => 'ENG301', 'course_name' => 'Advanced Engineering', 'semester' => 'Fall 2025', 'credits' => 3, 'is_active' => true],
        ['course_code' => 'CS202', 'course_name' => 'Data Structures', 'semester' => 'Spring 2026', 'credits' => 3, 'is_active' => true],
    ];

    $courseIds = [];
    foreach ($courses as $course) {
        $courseId = $courseModel->create($course);
        $courseIds[$course['course_code']] = $courseId;
    }

    echo "Creating course assignments...\n";

    // Assign professors to courses
    $professorModel->assignToCourse(1, $courseIds['CS101'], 'instructor');
    $professorModel->assignToCourse(2, $courseIds['MATH201'], 'instructor');
    $professorModel->assignToCourse(3, $courseIds['ENG301'], 'instructor');
    $professorModel->assignToCourse(1, $courseIds['CS202'], 'instructor');

    echo "Enrolling students in courses...\n";

    // Enroll students in courses
    $enrollments = [
        [1, $courseIds['CS101']],
        [1, $courseIds['MATH201']],
        [2, $courseIds['CS101']],
        [2, $courseIds['ENG301']],
        [3, $courseIds['MATH201']],
        [3, $courseIds['ENG301']],
        [4, $courseIds['CS101']],
        [4, $courseIds['CS202']],
    ];

    foreach ($enrollments as [$studentId, $courseId]) {
        $studentModel->enrollInCourse($studentId, $courseId, '2025-09-01');
    }

    echo "Creating sessions...\n";
    $sessionModel = new SessionModel();

    $now = new DateTime();
    $future = (clone $now)->add(new DateInterval('PT1H')); // 1 hour from now

    $sessions = [
        [
            'course_id' => $courseIds['CS101'],
            'professor_id' => 1,
            'title' => 'Lecture 1: Introduction',
            'session_date' => $now->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'expires_at' => $future->format('Y-m-d H:i:s'),
            'is_active' => true
        ],
        [
            'course_id' => $courseIds['MATH201'],
            'professor_id' => 2,
            'title' => 'Calculus Review',
            'session_date' => $now->format('Y-m-d'),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'expires_at' => $future->format('Y-m-d H:i:s'),
            'is_active' => true
        ],
    ];

    foreach ($sessions as $session) {
        $sessionModel->create($session);
    }

    // Commit transaction
    $db->commit();

    echo "\n✅ Test data seeded successfully!\n";
    echo "Students created: " . count($students) . "\n";
    echo "Professors created: " . count($professors) . "\n";
    echo "Courses created: " . count($courses) . "\n";
    echo "Sessions created: " . count($sessions) . "\n";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "❌ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
