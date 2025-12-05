<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class AttendanceModel extends Model
{
    protected string $table = 'attendance';
    protected string $primaryKey = 'id';
    protected array $fillable = ['session_id', 'student_id', 'status', 'notes'];

    /**
     * Record attendance
     */
    public function recordAttendance(int $sessionId, int $studentId, string $status = 'present', ?string $notes = null): bool
    {
        $data = [
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'status' => $status,
            'notes' => $notes
        ];

        try {
            $this->create($data);
            return true;
        } catch (\PDOException $e) {
            // Check if it's a duplicate entry error
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                throw new \RuntimeException('Attendance already recorded for this session');
            }
            throw $e;
        }
    }

    /**
     * Check if attendance already exists
     */
    public function attendanceExists(int $sessionId, int $studentId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM attendance
                WHERE session_id = ? AND student_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId, $studentId]);
        $result = $stmt->fetch();

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Get student's attendance for a course
     */
    public function getStudentCourseAttendance(int $studentId, int $courseId): array
    {
        $sql = "SELECT a.*, sess.title, sess.session_date, sess.start_time,
                       sess.end_time, sess.qr_token
                FROM attendance a
                JOIN sessions sess ON a.session_id = sess.id
                WHERE a.student_id = ? AND sess.course_id = ?
                ORDER BY sess.session_date DESC, sess.start_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentId, $courseId]);
        return $stmt->fetchAll();
    }

    /**
     * Get attendance statistics for a student
     */
    public function getStudentStats(int $studentId): array
    {
        $sql = "SELECT
                    COUNT(*) as total_sessions,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                    COUNT(DISTINCT sess.course_id) as courses_count
                FROM attendance a
                JOIN sessions sess ON a.session_id = sess.id
                WHERE a.student_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentId]);
        return $stmt->fetch() ?: [];
    }

    /**
     * Get attendance report for a course
     */
    public function getCourseReport(int $courseId): array
    {
        $sql = "SELECT
                    s.id as student_id,
                    s.student_id as student_number,
                    s.name as student_name,
                    COUNT(DISTINCT sess.id) as total_sessions,
                    COUNT(a.id) as attended_sessions,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                    ROUND(COUNT(a.id) * 100.0 / COUNT(DISTINCT sess.id), 2) as attendance_percentage
                FROM students s
                JOIN student_courses sc ON s.id = sc.student_id AND sc.status = 'enrolled'
                LEFT JOIN sessions sess ON sess.course_id = sc.course_id AND sess.is_active = TRUE
                LEFT JOIN attendance a ON a.session_id = sess.id AND a.student_id = s.id
                WHERE sc.course_id = ?
                GROUP BY s.id, s.student_id, s.name
                ORDER BY s.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }
}
