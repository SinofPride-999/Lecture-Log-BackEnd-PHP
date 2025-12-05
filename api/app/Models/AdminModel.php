<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class AdminModel extends Model
{
    protected string $table = 'admins';
    protected string $primaryKey = 'id';
    protected array $fillable = ['username', 'email', 'password_hash', 'full_name', 'is_active'];
    protected array $hidden = ['password_hash'];

    /**
     * Find admin by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findOne(['username = ?'], [$username]);
    }

    /**
     * Find admin by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findOne(['email = ?'], [$email]);
    }

    /**
     * Verify admin password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $adminId): bool
    {
        $sql = "UPDATE admins SET last_login = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$adminId]);
    }
}
