<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Models\StudentModel;
use App\Models\CourseModel;
use App\Models\SessionModel;

// Simple home route for testing
$router->get('/', function($request) {
    $controller = new HomeController($request);
    $controller->index();
});

// API routes
$router->group(function($router) {
    // Health check endpoint
    $router->get('/api/health', function($request) {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'healthy',
            'timestamp' => time(),
            'environment' => $_ENV['APP_ENV'] ?? 'unknown'
        ]);
    });

    // System status endpoint
    $router->get('/api/status', function($request) {
        try {
            $studentModel = new StudentModel();
            $professorModel = new App\Models\ProfessorModel();
            $courseModel = new CourseModel();
            $sessionModel = new SessionModel();
            $attendanceModel = new App\Models\AttendanceModel();

            $status = [
                'system' => 'Attendance System API',
                'status' => 'operational',
                'timestamp' => date('Y-m-d H:i:s'),
                'phase' => 'Phase 3: Student ID Validation',
                'database' => [
                    'students' => $studentModel->count(),
                    'professors' => $professorModel->count(),
                    'courses' => $courseModel->count(),
                    'sessions' => $sessionModel->count(),
                    'attendance_records' => $attendanceModel->count(),
                ],
                'features_ready' => [
                    'student_management' => true,
                    'course_management' => true,
                    'session_management' => true,
                    'attendance_tracking' => true,
                    'role_based_permissions' => true,
                    'qr_code_generation' => true,
                    'student_id_validation' => true,
                ],
                'next_features' => [
                    'jwt_authentication',
                    'api_endpoints',
                    'dashboard_apis',
                    'admin_panel'
                ]
            ];

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'System status',
                'data' => $status
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Status check failed',
                'error' => $e->getMessage()
            ]);
        }
    });

    // Test database connection
    $router->get('/api/test-db', function($request) {
        try {
            $db = \App\Models\Database::getConnection();
            $stmt = $db->query("SELECT 1 as test");
            $result = $stmt->fetch();

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Database connection successful',
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ]);
        }
    });

    // Test models
    $router->get('/api/test-models', function($request) {
        try {
            $studentModel = new StudentModel();
            $courseModel = new CourseModel();
            $sessionModel = new SessionModel();

            $results = [
                'students_count' => $studentModel->count(),
                'courses_count' => $courseModel->count(),
                'sessions_count' => $sessionModel->count(),
                'students' => $studentModel->findAll([], [], [], 5),
                'courses' => $courseModel->findAll([], [], [], 5),
            ];

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Models test successful',
                'data' => $results
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Models test failed',
                'error' => $e->getMessage()
            ]);
        }
    });

    // Test student enrollments
    $router->get('/api/test-enrollments', function($request) {
        try {
            $studentModel = new StudentModel();
            $student = $studentModel->find(1, ['courses']);

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Student enrollments test',
                'data' => $student
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Enrollments test failed',
                'error' => $e->getMessage()
            ]);
        }
    });

    // Test session creation
    $router->get('/api/test-session', function($request) {
        try {
            $sessionModel = new SessionModel();
            $qrToken = $sessionModel->generateQrToken();

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'QR token generated',
                'data' => [
                    'qr_token' => $qrToken,
                    'length' => strlen($qrToken)
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Session test failed',
                'error' => $e->getMessage()
            ]);
        }
    });

    // API Documentation endpoint
    $router->get('/api/docs', function($request) {
        $docs = [
            'system' => 'Attendance System API',
            'version' => '1.0.0',
            'phase' => 'Phase 3: Student ID Validation System',
            'endpoints' => [
                'GET /' => 'API Information',
                'GET /api/health' => 'Health Check',
                'GET /api/status' => 'System Status',
                'GET /api/docs' => 'This documentation',
                'GET /api/test-db' => 'Test Database Connection',
                'GET /api/test-models' => 'Test All Models',
                'GET /api/test-enrollments' => 'Test Student Enrollments',
                'GET /api/test-session' => 'Test Session QR Generation',
                'GET /api/auth/check-id' => 'Check if student ID is valid',
                'GET /api/auth/test-ids' => 'Test ID Manager',
                'POST /api/auth/register' => 'Student Registration',
                'GET /api/auth/student/{id}' => 'Get Student by ID',
                'GET /api/admin/student-ids' => 'Get all valid student IDs (Admin)',
                'POST /api/admin/student-ids' => 'Add a student ID (Admin)',
                'DELETE /api/admin/student-ids' => 'Remove a student ID (Admin)',
                'POST /api/admin/student-ids/bulk' => 'Bulk upload student IDs (Admin)',
            ],
            'models_ready' => [
                'StudentModel' => 'Student management with is_rep flag',
                'ProfessorModel' => 'Professor management',
                'CourseModel' => 'Course management with semester',
                'SessionModel' => 'Session management with QR tokens',
                'AttendanceModel' => 'Attendance recording with duplicate prevention',
                'AdminModel' => 'Admin user management',
            ],
            'services_ready' => [
                'StudentIdManager' => 'File-based student ID validation with auto-reload',
            ],
            'next_phase' => 'Phase 4: Authentication & Authorization',
            'database' => [
                'tables' => 8,
                'views' => 2,
                'seeded_data' => '4 students, 3 professors, 4 courses, 2 sessions'
            ]
        ];

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($docs, JSON_PRETTY_PRINT);
    });

    // === PHASE 3: AUTHENTICATION ROUTES ===

    // Check if student ID is valid
    $router->get('/api/auth/check-id', function($request) {
        $controller = new AuthController($request);
        $controller->checkId();
    });

    // Test ID manager
    $router->get('/api/auth/test-ids', function($request) {
        $controller = new AuthController($request);
        $controller->testIdManager();
    });

    // Student registration
    $router->post('/api/auth/register', function($request) {
        $controller = new AuthController($request);
        $controller->register();
    });

    // Get student by ID
    $router->get('/api/auth/student/{id}', function($request, $params) {
        $controller = new AuthController($request);
        $controller->getStudent($params['id']);
    });

    // === ADMIN ROUTES (Phase 3) ===

    // Get all valid student IDs
    $router->get('/api/admin/student-ids', function($request) {
        $controller = new AdminController($request);
        $controller->getAllIds();
    });

    // Add a student ID
    $router->post('/api/admin/student-ids', function($request) {
        $controller = new AdminController($request);
        $controller->addId();
    });

    // Remove a student ID
    $router->delete('/api/admin/student-ids', function($request) {
        $controller = new AdminController($request);
        $controller->removeId();
    });

    // Bulk upload student IDs
    $router->post('/api/admin/student-ids/bulk', function($request) {
        $controller = new AdminController($request);
        $controller->bulkUpload();
    });
});
