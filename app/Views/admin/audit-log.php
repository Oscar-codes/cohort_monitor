<?php
use App\Core\Auth;

$entries = isset($entries) && is_array($entries) ? $entries : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$actions = isset($actions) && is_array($actions) ? $actions : [];
$entityTypes = isset($entityTypes) && is_array($entityTypes) ? $entityTypes : [];
$users = isset($users) && is_array($users) ? $users : [];

$formatJson = static function ($value): string {
    if ($value === null || $value === '') {
        return '—';
    }

    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return $value;
    }

    if (is_array($value)) {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    return (string) $value;
};
?>

<section class="cohorts-hero mb-4">
    <div>
        <div class="dashboard-eyebrow">
            <i class="bi bi-journal-text"></i>
            Auditoria de cambios
        </div>
        <h2 class="cohorts-hero__title">Bitacora de actualizaciones</h2>
        <p class="cohorts-hero__copy">Historial de ediciones y acciones sobre usuarios, cohortes y otros modulos.</p>
    </div>
</section>

<div class="app-panel cohort-filter-panel mb-4">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-funnel text-primary"></i> Filtros</h3>
            <p class="app-panel__subtitle">Refina por usuario, accion, entidad, rango de fechas o texto.</p>
        </div>
    </div>

    <form method="GET" action="/admin/audit-log" class="row g-3">
        <div class="col-12 col-md-4">
            <label for="q" class="form-label">Busqueda</label>
            <input type="text" id="q" name="q" class="form-control" value="<?= htmlspecialchars((string) ($filters['q'] ?? '')) ?>" placeholder="Usuario, accion o entidad">
        </div>
        <div class="col-12 col-md-4">
            <label for="action" class="form-label">Accion</label>
            <select id="action" name="action" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= htmlspecialchars((string) $action) ?>" <?= (($filters['action'] ?? '') === $action) ? 'selected' : '' ?>><?= htmlspecialchars((string) $action) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label for="entity_type" class="form-label">Entidad</label>
            <select id="entity_type" name="entity_type" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($entityTypes as $entityType): ?>
                    <option value="<?= htmlspecialchars((string) $entityType) ?>" <?= (($filters['entity_type'] ?? '') === $entityType) ? 'selected' : '' ?>><?= htmlspecialchars((string) $entityType) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label for="user_id" class="form-label">Usuario</label>
            <select id="user_id" name="user_id" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($users as $user): ?>
                    <?php $id = (string) ($user['id'] ?? ''); ?>
                    <option value="<?= htmlspecialchars($id) ?>" <?= (($filters['user_id'] ?? '') === $id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($user['full_name'] ?? $user['username'] ?? 'Usuario')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="start_date" class="form-label">Desde</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars((string) ($filters['start_date'] ?? '')) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label for="end_date" class="form-label">Hasta</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars((string) ($filters['end_date'] ?? '')) ?>">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Aplicar filtros
            </button>
            <a href="/admin/audit-log" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-x-circle me-1"></i> Limpiar
            </a>
        </div>
    </form>
</div>

<section class="app-panel">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-list-check"></i> Eventos</h3>
            <p class="app-panel__subtitle">Total mostrado: <?= count($entries) ?> eventos.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Accion</th>
                    <th>Entidad</th>
                    <th>Ref</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No hay eventos para los filtros seleccionados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td class="text-nowrap"><?= htmlspecialchars((string) ($entry['created_at'] ?? '—')) ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars((string) ($entry['user_name'] ?? $entry['username'] ?? 'Sistema')) ?></div>
                                <small class="text-muted"><?= htmlspecialchars((string) ($entry['user_role'] ?? '')) ?></small>
                            </td>
                            <td><span class="badge text-bg-primary"><?= htmlspecialchars((string) ($entry['action'] ?? '—')) ?></span></td>
                            <td><?= htmlspecialchars((string) ($entry['entity_type'] ?? '—')) ?></td>
                            <td><?= htmlspecialchars((string) ($entry['entity_ref'] ?? '—')) ?></td>
                            <td>
                                <details>
                                    <summary class="small text-primary" style="cursor: pointer;">Ver payload</summary>
                                    <div class="small text-muted mt-2">OLD</div>
                                    <pre class="small mb-2"><?= htmlspecialchars($formatJson($entry['old_values'] ?? null)) ?></pre>
                                    <div class="small text-muted">NEW</div>
                                    <pre class="small mb-0"><?= htmlspecialchars($formatJson($entry['new_values'] ?? null)) ?></pre>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
