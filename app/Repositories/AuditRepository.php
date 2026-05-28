<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * AuditRepository — Data-access for the audit_log table.
 */
class AuditRepository
{
    private Database $db;
    private ?bool $usesEntityKey = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Record an audit entry. */
    public function log(array $data): void
    {
        $entityRaw = $data['entity_key'] ?? ($data['entity_id'] ?? null);
        $baseParams = [
            'user_id'     => $data['user_id'] ?? null,
            'action'      => $data['action'],
            'entity_type' => $data['entity_type'],
            'old_values'  => isset($data['old_values']) ? json_encode($data['old_values']) : null,
            'new_values'  => isset($data['new_values']) ? json_encode($data['new_values']) : null,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        if ($this->auditUsesEntityKey()) {
            $this->db->execute(
                'INSERT INTO audit_log (user_id, action, entity_type, entity_key, old_values, new_values, ip_address, created_at)
                 VALUES (:user_id, :action, :entity_type, :entity_key, :old_values, :new_values, :ip, NOW())',
                $baseParams + [
                    'entity_key' => $entityRaw !== null ? (string) $entityRaw : null,
                ]
            );
            return;
        }

        $this->db->execute(
            'INSERT INTO audit_log (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, created_at)
             VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip, NOW())',
            $baseParams + [
                'entity_id' => $entityRaw !== null && is_numeric((string) $entityRaw) ? (int) $entityRaw : null,
            ]
        );
    }

    /** Recent audit entries (limit). */
    public function findRecent(int $limit = 50): array
    {
        return $this->db->query(
            'SELECT al.*, u.full_name AS user_name, u.role AS user_role
             FROM audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT ' . (int) $limit
        );
    }

    /** Entries for a specific entity. */
    public function findByEntity(string $type, int|string $id): array
    {
        if (!$this->auditUsesEntityKey()) {
            return $this->db->query(
                'SELECT al.*, u.full_name AS user_name
                 FROM audit_log al
                 LEFT JOIN users u ON u.id = al.user_id
                 WHERE al.entity_type = :t AND al.entity_id = :eid
                 ORDER BY al.created_at DESC',
                ['t' => $type, 'eid' => (int) $id]
            );
        }

        return $this->db->query(
            'SELECT al.*, u.full_name AS user_name
             FROM audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.entity_type = :t AND al.entity_key = :ek
             ORDER BY al.created_at DESC',
            ['t' => $type, 'ek' => (string) $id]
        );
    }

    private function auditUsesEntityKey(): bool
    {
        if ($this->usesEntityKey !== null) {
            return $this->usesEntityKey;
        }

        try {
            $rows = $this->db->query('SHOW COLUMNS FROM audit_log LIKE :column', ['column' => 'entity_key']);
            $this->usesEntityKey = !empty($rows);
        } catch (\Throwable $e) {
            $this->usesEntityKey = false;
        }

        return $this->usesEntityKey;
    }
}
