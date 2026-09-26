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

<section class="kodigo-card" data-elevation="1">
    <div class="kodigo-card__header">
        <div>
            <h3 class="kodigo-card__title"><i class="bi bi-activity"></i> Health checks</h3>
            <p class="kodigo-card__subtitle">Resumen rapido del estado de base de datos y tablas criticas.</p>
        </div>
    </div>
    <div class="kodigo-card__body">

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
                            $statusTone = $status === 'ok' ? 'success' : ($status === 'error' ? 'danger' : 'warning');
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars((string) ($check['name'] ?? 'Check')) ?></td>
                                <td><span class="kodigo-pill" data-tone="<?= htmlspecialchars($statusTone) ?>"><span class="kodigo-pill__dot" aria-hidden="true"></span><?= htmlspecialchars($status) ?></span></td>
                                <td><?= htmlspecialchars((string) ($check['detail'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
