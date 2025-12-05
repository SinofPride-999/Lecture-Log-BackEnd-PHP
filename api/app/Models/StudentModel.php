<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class StudentModel extends Model
{
    protected string $table = 'students';
    protected string $primaryKey = 'id';
    protected array $fillable = ['student_id', 'name', 'email', 'is_rep', 'is_approved'];
    protected array $hidden = []; // Add sensitive fields here if needed

    /**
     * Find student by student ID (not primary key)
     */
    public function findByStudentId(string $studentId): ?array
    {
        return $this->findOne(['student_id = ?'], [$studentId]);
    }

    /**
     * Find students by course
     */
    public function findByCourse(int $courseId, array $with = []): array
    {
        $sql = "SELECT s.* FROM students s
                JOIN student_courses sc ON s.id = sc.student_id
                WHERE sc.course_id = ? AND sc.status = 'enrolled'
                ORDER BY s.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$courseId]);
        $students = $stmt->fetchAll();

        if (!empty($with) && !empty($students)) {
            foreach ($students as &$student) {
                $student = $this->loadRelationships($student, $with);
            }
        }

        return $students;
    }

    /**
     * Get student's courses
     */
    public function courses(int $studentId): array
    {
        $sql = "SELECT c.*, sc.enrollment_date, sc.status
                FROM courses c
                JOIN student_courses sc ON c.id = sc.course_id
                WHERE sc.student_id = ? AND sc.status = 'enrolled'
                ORDER BY c.semester DESC, c.course_code";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    /**
     * Get student's attendance records
     */
    public function attendance(int $studentId, ?int $courseId = null): array
    {
        $conditions = ['a.student_id = ?'];
        $params = [$studentId];

        if ($courseId) {
            $conditions[] = 's.course_id = ?';
            $params[] = $courseId;
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT a.*, s.title as session_title, s.session_date, s.start_time,
                       c.course_code, c.course_name
                FROM attendance a
                JOIN sessions s ON a.session_id = s.id
                JOIN courses c ON s.course_id = c.id
                WHERE {$where}
                ORDER BY s.session_date DESC, s.start_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Enroll student in a course
     */
    public function enrollInCourse(int $studentId, int $courseId, string $enrollmentDate): bool
    {
        $sql = "INSERT INTO student_courses (student_id, course_id, enrollment_date, status)
                VALUES (?, ?, ?, 'enrolled')
                ON DUPLICATE KEY UPDATE status = 'enrolled', enrollment_date = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$studentId, $courseId, $enrollmentDate, $enrollmentDate]);
    }

    /**
     * Get course reps
     */
    public function getCourseReps(?int $courseId = null): array
    {
        $conditions = ['s.is_rep = TRUE', 's.is_approved = TRUE'];
        $params = [];

        if ($courseId) {
            $conditions[] = 'sc.course_id = ?';
            $params[] = $courseId;

            $sql = "SELECT s.* FROM students s
                    JOIN student_courses sc ON s.id = sc.student_id
                    WHERE " . implode(' AND ', $conditions) . "
                    ORDER BY s.name";
        } else {
            $sql = "SELECT s.* FROM students s
                    WHERE " . implode(' AND ', $conditions) . "
                    ORDER BY s.name";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
