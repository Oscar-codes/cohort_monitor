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
        $entityRefSelect = $this->auditUsesEntityKey()
            ? 'al.entity_key AS entity_ref'
            : 'CAST(al.entity_id AS CHAR) AS entity_ref';

        return $this->db->query(
            'SELECT al.*, ' . $entityRefSelect . ', u.full_name AS user_name, u.role AS user_role
             FROM audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT ' . (int) $limit
        );
    }

    /**
     * Filtered audit entries for admin view.
     */
    public function findFiltered(array $filters, int $limit = 200): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['action'])) {
            $where[] = 'al.action = :action';
            $params['action'] = (string) $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'al.entity_type = :entity_type';
            $params['entity_type'] = (string) $filters['entity_type'];
        }

        if (!empty($filters['user_id']) && ctype_digit((string) $filters['user_id'])) {
            $where[] = 'al.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = 'al.created_at >= :start_date';
            $params['start_date'] = (string) $filters['start_date'] . ' 00:00:00';
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'al.created_at <= :end_date';
            $params['end_date'] = (string) $filters['end_date'] . ' 23:59:59';
        }

        if (!empty($filters['q'])) {
            $qLike = '%' . trim((string) $filters['q']) . '%';
            if ($this->auditUsesEntityKey()) {
                $where[] = '(u.full_name LIKE :q0 OR u.username LIKE :q1 OR al.action LIKE :q2 OR al.entity_type LIKE :q3 OR al.entity_key LIKE :q4)';
            } else {
                $where[] = '(u.full_name LIKE :q0 OR u.username LIKE :q1 OR al.action LIKE :q2 OR al.entity_type LIKE :q3 OR CAST(al.entity_id AS CHAR) LIKE :q4)';
            }
            $params['q0'] = $qLike;
            $params['q1'] = $qLike;
            $params['q2'] = $qLike;
            $params['q3'] = $qLike;
            $params['q4'] = $qLike;
        }

        $entityRefSelect = $this->auditUsesEntityKey()
            ? 'al.entity_key AS entity_ref'
            : 'CAST(al.entity_id AS CHAR) AS entity_ref';

        return $this->db->query(
            'SELECT al.*, ' . $entityRefSelect . ', u.username, u.full_name AS user_name, u.role AS user_role
             FROM audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY al.created_at DESC
             LIMIT ' . (int) $limit,
            $params
        );
    }

    public function getActionOptions(): array
    {
        $rows = $this->db->query('SELECT DISTINCT action FROM audit_log ORDER BY action ASC');
        return array_values(array_filter(array_map(static fn(array $r): string => (string) ($r['action'] ?? ''), $rows)));
    }

    public function getEntityTypeOptions(): array
    {
        $rows = $this->db->query('SELECT DISTINCT entity_type FROM audit_log ORDER BY entity_type ASC');
        return array_values(array_filter(array_map(static fn(array $r): string => (string) ($r['entity_type'] ?? ''), $rows)));
    }

    public function getUserOptions(): array
    {
        return $this->db->query(
            'SELECT id, username, full_name
             FROM users
             WHERE is_active = 1
             ORDER BY full_name ASC'
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
            $rows = $this->db->query("SHOW COLUMNS FROM audit_log LIKE 'entity_key'");
            $this->usesEntityKey = !empty($rows);
        } catch (\Throwable $e) {
            $this->usesEntityKey = false;
        }

        return $this->usesEntityKey;
    }
}
