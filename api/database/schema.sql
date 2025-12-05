-- Create attendance system database
CREATE DATABASE IF NOT EXISTS lecture_log
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE lecture_log;

-- Students table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    is_rep BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_student_id (student_id),
    INDEX idx_email (email),
    INDEX idx_is_approved (is_approved)
) ENGINE=InnoDB;

-- Professors table
CREATE TABLE professors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    professor_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_professor_id (professor_id),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Courses table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(200) NOT NULL,
    semester VARCHAR(20) NOT NULL, -- e.g., "Fall 2025", "Spring 2026"
    credits INT DEFAULT 3,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_course_code (course_code),
    INDEX idx_semester (semester),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- Sessions table (QR sessions for attendance)
CREATE TABLE sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    professor_id INT NOT NULL,
    qr_token VARCHAR(255) UNIQUE NOT NULL,
    title VARCHAR(200),
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE,

    INDEX idx_course_id (course_id),
    INDEX idx_professor_id (professor_id),
    INDEX idx_qr_token (qr_token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- Attendance records
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('present', 'late', 'excused') DEFAULT 'present',
    notes TEXT,

    -- Unique constraint to prevent duplicate attendance
    UNIQUE KEY unique_attendance (session_id, student_id),

    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,

    INDEX idx_session_id (session_id),
    INDEX idx_student_id (student_id),
    INDEX idx_scanned_at (scanned_at),
    INDEX idx_session_student (session_id, student_id)
) ENGINE=InnoDB;

-- Student course enrollments (many-to-many)
CREATE TABLE student_courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('enrolled', 'dropped', 'completed') DEFAULT 'enrolled',

    UNIQUE KEY unique_enrollment (student_id, course_id),

    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,

    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Professor course assignments (many-to-many)
CREATE TABLE professor_courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    professor_id INT NOT NULL,
    course_id INT NOT NULL,
    role ENUM('instructor', 'ta', 'coordinator') DEFAULT 'instructor',
    assigned_date DATE NOT NULL,

    UNIQUE KEY unique_assignment (professor_id, course_id),

    FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,

    INDEX idx_professor_id (professor_id),
    INDEX idx_course_id (course_id)
) ENGINE=InnoDB;

-- Admin users (for system administrators)
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- Insert sample admin (password: admin123)
INSERT INTO admins (username, email, password_hash, full_name)
VALUES ('admin', 'admin@attendance.system', '$2y$10$YourHashedPasswordHere', 'System Administrator');

-- Create a view for student attendance summary
CREATE VIEW student_attendance_summary AS
SELECT
    s.id as student_id,
    s.student_id as student_number,
    s.name as student_name,
    c.id as course_id,
    c.course_code,
    c.course_name,
    COUNT(a.id) as total_sessions,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
    SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count
FROM students s
JOIN student_courses sc ON s.id = sc.student_id AND sc.status = 'enrolled'
JOIN courses c ON sc.course_id = c.id AND c.is_active = TRUE
LEFT JOIN sessions sess ON sess.course_id = c.id AND sess.is_active = TRUE
LEFT JOIN attendance a ON a.session_id = sess.id AND a.student_id = s.id
GROUP BY s.id, c.id;

-- Create a view for course attendance statistics
CREATE VIEW course_attendance_stats AS
SELECT
    c.id as course_id,
    c.course_code,
    c.course_name,
    c.semester,
    COUNT(DISTINCT sess.id) as total_sessions,
    COUNT(DISTINCT sc.student_id) as enrolled_students,
    COUNT(a.id) as total_attendance_records,
    AVG(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as attendance_rate
FROM courses c
LEFT JOIN sessions sess ON sess.course_id = c.id AND sess.is_active = TRUE
LEFT JOIN student_courses sc ON sc.course_id = c.id AND sc.status = 'enrolled'
LEFT JOIN attendance a ON a.session_id = sess.id
GROUP BY c.id;
