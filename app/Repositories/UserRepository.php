<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * UserRepository — Data-access for the users table.
 */
class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(): array
    {
        return $this->db->query('SELECT * FROM users ORDER BY created_at DESC');
    }

    public function findById(int $id): ?array
    {
        $rows = $this->db->query('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
        return $rows[0] ?? null;
    }

    public function findByUsername(string $username): ?array
    {
        $rows = $this->db->query(
            'SELECT * FROM users WHERE username = :u LIMIT 1',
            ['u' => $username]
        );
        return $rows[0] ?? null;
    }

    public function findByEmail(string $email): ?array
    {
        $rows = $this->db->query(
            'SELECT * FROM users WHERE email = :e LIMIT 1',
            ['e' => $email]
        );
        return $rows[0] ?? null;
    }

    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
             VALUES (:username, :email, :password_hash, :full_name, :role, :is_active, NOW(), NOW())',
            [
                'username'      => $data['username'],
                'email'         => $data['email'],
                'password_hash' => $data['password_hash'],
                'full_name'     => $data['full_name'],
                'role'          => $data['role'] ?? 'admin',
                'is_active'     => $data['is_active'] ?? 1,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sets   = [];
        $params = ['id' => $id];

        $allowed = ['username', 'email', 'full_name', 'role', 'is_active'];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]        = "{$col} = :{$col}";
                $params[$col]  = $data[$col];
            }
        }

        if (!empty($data['password_hash'])) {
            $sets[]                  = 'password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }

        if (empty($sets)) {
            return false;
        }

        $sets[] = 'updated_at = NOW()';
        $sql    = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        return $this->db->execute($sql, $params) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM users WHERE id = :id', ['id' => $id]) > 0;
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->execute(
            'UPDATE users SET last_login_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }

    public function count(): int
    {
        $r = $this->db->query('SELECT COUNT(*) as total FROM users');
        return (int) ($r[0]['total'] ?? 0);
    }
}
