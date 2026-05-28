<?php
$checks = isset($checks) && is_array($checks) ? $checks : [];
?>

<section class="cohorts-hero mb-4">
    <div>
        <div class="dashboard-eyebrow">
            <i class="bi bi-heart-pulse"></i>
            Diagnostico tecnico
        </div>
        <h2 class="cohorts-hero__title">Estado del sistema</h2>
        <p class="cohorts-hero__copy">Validaciones de conexion y esquema para soporte operativo en produccion.</p>
    </div>
</section>

<section class="app-panel">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-activity"></i> Health checks</h3>
            <p class="app-panel__subtitle">Resumen rapido del estado de base de datos y tablas criticas.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Check</th>
                    <th>Estado</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($checks)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">Sin resultados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($checks as $check): ?>
                        <?php
                        $status = (string) ($check['status'] ?? 'warn');
                        $badgeClass = $status === 'ok' ? 'text-bg-success' : ($status === 'error' ? 'text-bg-danger' : 'text-bg-warning');
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars((string) ($check['name'] ?? 'Check')) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span></td>
                            <td><?= htmlspecialchars((string) ($check['detail'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
