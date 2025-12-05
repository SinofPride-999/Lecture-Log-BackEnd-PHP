<?php

namespace App\Core;

use App\Models\Database;
use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $relationships = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Find record by ID
     */
    public function find(int $id, array $with = []): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch() ?: null;

        if ($record && !empty($with)) {
            $record = $this->loadRelationships($record, $with);
        }

        return $record;
    }

    /**
     * Find all records with optional conditions and limit
     */
    public function findAll(array $conditions = [], array $params = [], array $with = [], ?int $limit = null): array
    {
        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $limitClause = '';
        if ($limit !== null) {
            $limitClause = "LIMIT {$limit}";
        }

        $sql = "SELECT * FROM {$this->table} {$where} {$limitClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        if (!empty($with) && !empty($records)) {
            foreach ($records as &$record) {
                $record = $this->loadRelationships($record, $with);
            }
        }

        return $records;
    }

    /**
     * Find one record by conditions
     */
    public function findOne(array $conditions, array $params = [], array $with = []): ?array
    {
        $where = implode(' AND ', $conditions);
        $sql = "SELECT * FROM {$this->table} WHERE {$where} LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $record = $stmt->fetch() ?: null;

        if ($record && !empty($with)) {
            $record = $this->loadRelationships($record, $with);
        }

        return $record;
    }

    /**
     * Create new record
     */
    public function create(array $data): int
    {
        // Filter data to only include fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update record
     */
    public function update(int $id, array $data): bool
    {
        // Filter data to only include fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $set);

        $data['id'] = $id;
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($data);
    }

    /**
     * Delete record
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Count records with optional conditions
     */
    public function count(array $conditions = [], array $params = []): int
    {
        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (int)($result['count'] ?? 0);
    }

    /**
     * Paginate results
     */
    public function paginate(int $page = 1, int $perPage = 15, array $conditions = [], array $params = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = '';

        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        // Get total count
        $total = $this->count($conditions, $params);

        // Get paginated data
        $sql = "SELECT * FROM {$this->table} {$where} LIMIT :offset, :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total)
            ]
        ];
    }

    /**
     * Begin a database transaction
     */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        $this->db->rollBack();
    }

    /**
     * Load relationships for a record
     */
    protected function loadRelationships(array $record, array $with): array
    {
        foreach ($with as $relation) {
            if (method_exists($this, $relation)) {
                $record[$relation] = $this->$relation($record[$this->primaryKey]);
            }
        }
        return $record;
    }

    /**
     * Hide sensitive fields from output
     */
    protected function hideFields(array $record): array
    {
        if (!empty($this->hidden)) {
            foreach ($this->hidden as $field) {
                unset($record[$field]);
            }
        }
        return $record;
    }
}
