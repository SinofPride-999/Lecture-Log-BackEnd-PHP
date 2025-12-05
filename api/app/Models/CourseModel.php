<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class CourseModel extends Model
{
    protected string $table = 'courses';
    protected string $primaryKey = 'id';
    protected array $fillable = ['course_code', 'course_name', 'semester', 'credits', 'is_active'];

    /**
     * Find course by course code
     */
    public function findByCode(string $courseCode): ?array
    {
        return $this->findOne(['course_code = ?'], [$courseCode]);
    }

    /**
     * Get courses by semester
     */
    public function findBySemester(string $semester): array
    {
        return $this->findAll(['semester = ?'], [$semester]);
    }

    /**
     * Get active courses
     */
    public function getActiveCourses(): array
    {
        return $this->findAll(['is_active = TRUE']);
    }

    /**
     * Get course enrollment count
     */
    public function getEnrollmentCount(int $courseId): int
    {
        $sql = "SELECT COUNT(*) as count FROM student_courses
                WHERE course_id = ? AND status = 'enrolled'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$courseId]);
        $result = $stmt->fetch();

        return (int)($result['count'] ?? 0);
    }

    /**
     * Get course professors
     */
    public function professors(int $courseId): array
    {
        $sql = "SELECT p.*, pc.role, pc.assigned_date
                FROM professors p
                JOIN professor_courses pc ON p.id = pc.professor_id
                WHERE pc.course_id = ?
                ORDER BY pc.role, p.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    /**
     * Get course sessions
     */
    public function sessions(int $courseId, bool $activeOnly = true): array
    {
        $conditions = ['course_id = ?'];
        $params = [$courseId];

        if ($activeOnly) {
            $conditions[] = 'is_active = TRUE';
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT * FROM sessions
                WHERE {$where}
                ORDER BY session_date DESC, start_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
