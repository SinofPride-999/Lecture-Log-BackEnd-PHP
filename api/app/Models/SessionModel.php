<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use RuntimeException;

class SessionModel extends Model
{
    protected string $table = 'sessions';
    protected string $primaryKey = 'id';
    protected array $fillable = ['course_id', 'professor_id', 'qr_token', 'title',
                                'session_date', 'start_time', 'end_time', 'expires_at', 'is_active'];

    /**
     * Create a new session with QR token
     */
    public function create(array $data): int
    {
        // Generate QR token
        if (empty($data['qr_token'])) {
            $data['qr_token'] = $this->generateQrToken();
        }

        return parent::create($data);
    }

    /**
     * Generate secure QR token
     */
    public function generateQrToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Find session by QR token
     */
    public function findByQrToken(string $qrToken): ?array
    {
        return $this->findOne(['qr_token = ?'], [$qrToken]);
    }

    /**
     * Check if session is active and not expired
     */
    public function isSessionActive(array $session): bool
    {
        if (!$session['is_active']) {
            return false;
        }

        $now = new \DateTime();
        $expiresAt = new \DateTime($session['expires_at']);

        return $now < $expiresAt;
    }

    /**
     * Get sessions for a course
     */
    public function getByCourse(int $courseId, bool $activeOnly = true): array
    {
        $conditions = ['course_id = ?'];
        $params = [$courseId];

        if ($activeOnly) {
            $conditions[] = 'is_active = TRUE';
            $conditions[] = 'expires_at > NOW()';
        }

        return $this->findAll($conditions, $params);
    }

    /**
     * Get attendance for a session
     */
    public function getAttendance(int $sessionId): array
    {
        $sql = "SELECT a.*, s.name as student_name, s.student_id,
                       s.email as student_email
                FROM attendance a
                JOIN students s ON a.student_id = s.id
                WHERE a.session_id = ?
                ORDER BY a.scanned_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }

    /**
     * Get attendance count for a session
     */
    public function getAttendanceCount(int $sessionId): int
    {
        $sql = "SELECT COUNT(*) as count FROM attendance
                WHERE session_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch();

        return (int)($result['count'] ?? 0);
    }

    /**
     * Expire old sessions
     */
    public function expireOldSessions(): int
    {
        $sql = "UPDATE sessions SET is_active = FALSE
                WHERE is_active = TRUE AND expires_at <= NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }
}
