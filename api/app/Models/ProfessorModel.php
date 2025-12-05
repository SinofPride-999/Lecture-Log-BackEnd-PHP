<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class ProfessorModel extends Model
{
    protected string $table = 'professors';
    protected string $primaryKey = 'id';
    protected array $fillable = ['professor_id', 'name', 'email', 'department'];

    /**
     * Find professor by professor ID
     */
    public function findByProfessorId(string $professorId): ?array
    {
        return $this->findOne(['professor_id = ?'], [$professorId]);
    }

    /**
     * Get professor's assigned courses
     */
    public function courses(int $professorId): array
    {
        $sql = "SELECT c.*, pc.role, pc.assigned_date
                FROM courses c
                JOIN professor_courses pc ON c.id = pc.course_id
                WHERE pc.professor_id = ?
                ORDER BY c.semester DESC, c.course_code";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$professorId]);
        return $stmt->fetchAll();
    }

    /**
     * Assign professor to a course
     */
    public function assignToCourse(int $professorId, int $courseId, string $role = 'instructor'): bool
    {
        $sql = "INSERT INTO professor_courses (professor_id, course_id, role, assigned_date)
                VALUES (?, ?, ?, CURDATE())
                ON DUPLICATE KEY UPDATE role = ?, assigned_date = CURDATE()";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$professorId, $courseId, $role, $role]);
    }

    /**
     * Check if professor is assigned to a course
     */
    public function isAssignedToCourse(int $professorId, int $courseId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM professor_courses
                WHERE professor_id = ? AND course_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$professorId, $courseId]);
        $result = $stmt->fetch();

        return ($result['count'] ?? 0) > 0;
    }
}
