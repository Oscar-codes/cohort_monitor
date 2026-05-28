<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Repositories\AuditRepository;

/**
 * AdminController
 *
 * Admin-only operational diagnostics and audit views.
 */
class AdminController extends Controller
{
    private AuditRepository $auditRepo;

    public function __construct()
    {
        Auth::requireRole('admin');
        $this->auditRepo = new AuditRepository();
    }

    public function auditLog(): void
    {
        $filters = [
            'q' => (string) $this->input('q', ''),
            'action' => (string) $this->input('action', ''),
            'entity_type' => (string) $this->input('entity_type', ''),
            'user_id' => (string) $this->input('user_id', ''),
            'start_date' => (string) $this->input('start_date', ''),
            'end_date' => (string) $this->input('end_date', ''),
        ];

        $activeFilters = array_filter($filters, static fn($value): bool => trim((string) $value) !== '');
        $entries = $this->auditRepo->findFiltered($filters, 300);

        $this->view('admin.audit-log', [
            'pageTitle' => 'Bitacora de Auditoria',
            'activePage' => 'admin-audit-log',
            'entries' => $entries,
            'filters' => $filters,
            'activeFilters' => $activeFilters,
            'actions' => $this->auditRepo->getActionOptions(),
            'entityTypes' => $this->auditRepo->getEntityTypeOptions(),
            'users' => $this->auditRepo->getUserOptions(),
        ]);
    }

    public function health(): void
    {
        $checks = [];

        try {
            $db = Database::getInstance()->getConnection();
            $db->query('SELECT 1');
            $checks[] = ['name' => 'Conexion DB', 'status' => 'ok', 'detail' => 'Conexion activa'];

            $schemaRows = $db->query('SELECT DATABASE() AS db')->fetchAll();
            $checks[] = [
                'name' => 'Base activa',
                'status' => 'ok',
                'detail' => (string) ($schemaRows[0]['db'] ?? 'desconocida'),
            ];

            $requiredTables = ['users', 'audit_log', 'cohorts'];
            foreach ($requiredTables as $table) {
                $rows = $db->query("SHOW TABLES LIKE '{$table}'")->fetchAll();
                $checks[] = [
                    'name' => 'Tabla ' . $table,
                    'status' => empty($rows) ? 'error' : 'ok',
                    'detail' => empty($rows) ? 'No encontrada' : 'Disponible',
                ];
            }

            $auditColumns = $db->query("SHOW COLUMNS FROM audit_log")->fetchAll();
            $columnNames = array_map(static fn(array $r): string => (string) ($r['Field'] ?? ''), $auditColumns);
            $hasEntityKey = in_array('entity_key', $columnNames, true);
            $hasEntityId = in_array('entity_id', $columnNames, true);

            $checks[] = [
                'name' => 'audit_log.entity_key',
                'status' => $hasEntityKey ? 'ok' : 'warn',
                'detail' => $hasEntityKey ? 'Presente' : 'Ausente (se usa fallback entity_id)',
            ];
            $checks[] = [
                'name' => 'audit_log.entity_id',
                'status' => $hasEntityId ? 'ok' : 'warn',
                'detail' => $hasEntityId ? 'Presente' : 'Ausente',
            ];
        } catch (\Throwable $e) {
            $checks[] = [
                'name' => 'Health check',
                'status' => 'error',
                'detail' => $e->getMessage(),
            ];
        }

        $this->view('admin.health', [
            'pageTitle' => 'Estado del Sistema',
            'activePage' => 'admin-health',
            'checks' => $checks,
        ]);
    }
}
