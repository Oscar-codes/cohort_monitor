<!-- Alerts Dashboard (All roles) -->
<?php use App\Services\MarketingService; ?>

<?php
    $riskComments = $riskComments ?? [];
    $atRiskStages = $atRiskStages ?? [];
    $risksByCohort = $risksByCohort ?? [];

    $roleLabels = [
        'admin'          => ['Administrador', 'bg-danger-subtle text-danger'],
        'admissions_b2b' => ['Admisiones B2B', 'bg-info-subtle text-info'],
        'admissions_b2c' => ['Admisiones B2C', 'bg-primary-subtle text-primary'],
        'marketing'      => ['Marketing', 'bg-warning-subtle text-warning'],
    ];

    $riskItems = [];
    $affectedCohorts = [];

    foreach ($atRiskStages as $stage) {
        $cohortId = (int) $stage['cohort_id'];
        $stageLabel = MarketingService::STAGE_LABELS[$stage['stage_name']] ?? $stage['stage_name'];
        $riskItems[] = [
            'type' => 'marketing',
            'severity' => 'Alta',
            'tone' => 'warning',
            'icon' => 'bi-megaphone',
            'cohort_id' => $cohortId,
            'cohort_code' => $stage['cohort_code'] ?? '',
            'cohort_name' => $stage['cohort_name'] ?? '',
            'title' => 'Etapa de marketing en riesgo',
            'detail' => $stageLabel,
            'body' => $stage['risk_notes'] ?? 'Sin notas registradas',
            'actor' => $stage['updated_by_name'] ?? 'Sin responsable',
            'date' => $stage['updated_at'] ?? null,
            'url' => '/cohorts/' . $cohortId . '/marketing',
            'cta' => 'Ver marketing',
        ];

        $affectedCohorts[$cohortId] ??= [
            'id' => $cohortId,
            'code' => $stage['cohort_code'] ?? '',
            'name' => $stage['cohort_name'] ?? '',
            'marketing' => 0,
            'comments' => 0,
            'latest' => $stage['updated_at'] ?? null,
        ];
        $affectedCohorts[$cohortId]['marketing']++;
        if (!empty($stage['updated_at']) && (!$affectedCohorts[$cohortId]['latest'] || $stage['updated_at'] > $affectedCohorts[$cohortId]['latest'])) {
            $affectedCohorts[$cohortId]['latest'] = $stage['updated_at'];
        }
    }

    foreach ($riskComments as $comment) {
        $cohortId = (int) $comment['cohort_id'];
        [$roleLabel, $roleClass] = $roleLabels[$comment['author_role'] ?? ''] ?? [$comment['author_role'] ?? 'Usuario', 'bg-secondary-subtle text-secondary'];
        $riskItems[] = [
            'type' => 'comment',
            'severity' => 'Critica',
            'tone' => 'danger',
            'icon' => 'bi-chat-left-dots',
            'cohort_id' => $cohortId,
            'cohort_code' => $comment['cohort_code'] ?? '',
            'cohort_name' => $comment['cohort_name'] ?? '',
            'title' => 'Comentario de riesgo',
            'detail' => $roleLabel,
            'detail_class' => $roleClass,
            'body' => $comment['body'] ?? '',
            'actor' => $comment['author_name'] ?? 'Sin autor',
            'date' => $comment['created_at'] ?? null,
            'url' => '/cohorts/' . $cohortId,
            'cta' => 'Ver cohorte',
        ];

        $affectedCohorts[$cohortId] ??= [
            'id' => $cohortId,
            'code' => $comment['cohort_code'] ?? '',
            'name' => $comment['cohort_name'] ?? '',
            'marketing' => 0,
            'comments' => 0,
            'latest' => $comment['created_at'] ?? null,
        ];
        $affectedCohorts[$cohortId]['comments']++;
        if (!empty($comment['created_at']) && (!$affectedCohorts[$cohortId]['latest'] || $comment['created_at'] > $affectedCohorts[$cohortId]['latest'])) {
            $affectedCohorts[$cohortId]['latest'] = $comment['created_at'];
        }
    }

    usort($riskItems, static fn(array $a, array $b): int => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
    uasort($affectedCohorts, static function (array $a, array $b): int {
        $totalCompare = (($b['marketing'] + $b['comments']) <=> ($a['marketing'] + $a['comments']));
        return $totalCompare !== 0 ? $totalCompare : strcmp((string) ($b['latest'] ?? ''), (string) ($a['latest'] ?? ''));
    });

    $totalRisks = count($riskItems);
    $commentCount = count($riskComments);
    $stageCount = count($atRiskStages);
    $affectedCount = count($affectedCohorts);
?>

<?php if (!empty($loadError)): ?>
<div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-exclamation-octagon"></i>
    <span><?= htmlspecialchars($loadError) ?></span>
</div>
<?php endif; ?>

<section class="alerts-hero mb-4">
    <div>
        <div class="dashboard-eyebrow">
            <i class="bi bi-shield-exclamation"></i>
            Centro de riesgos
        </div>
        <h2 class="alerts-hero__title">Alertas y riesgos activos</h2>
        <p class="alerts-hero__copy">Prioriza cohortes con etapas de marketing en riesgo y comentarios marcados por el equipo.</p>
    </div>
    <div class="alerts-hero__status <?= $totalRisks > 0 ? 'is-warning' : 'is-ok' ?>">
        <i class="bi <?= $totalRisks > 0 ? 'bi-exclamation-triangle' : 'bi-shield-check' ?>"></i>
        <span><?= $totalRisks > 0 ? number_format($totalRisks) . ' riesgos activos' : 'Sin riesgos activos' ?></span>
    </div>
</section>

<section class="row g-3 mb-4" aria-label="Resumen de riesgos">
    <div class="col-6 col-xl-3">
        <article class="risk-summary-card risk-summary-card--danger">
            <span><i class="bi bi-exclamation-triangle"></i></span>
            <div>
                <strong><?= number_format($totalRisks) ?></strong>
                <small>Total riesgos</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="risk-summary-card risk-summary-card--warning">
            <span><i class="bi bi-megaphone"></i></span>
            <div>
                <strong><?= number_format($stageCount) ?></strong>
                <small>Marketing</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="risk-summary-card risk-summary-card--primary">
            <span><i class="bi bi-chat-left-dots"></i></span>
            <div>
                <strong><?= number_format($commentCount) ?></strong>
                <small>Comentarios</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="risk-summary-card risk-summary-card--info">
            <span><i class="bi bi-people"></i></span>
            <div>
                <strong><?= number_format($affectedCount) ?></strong>
                <small>Cohortes afectadas</small>
            </div>
        </article>
    </div>
</section>

<?php if ($totalRisks > 0): ?>
<section class="row g-3">
    <div class="col-xl-8">
        <div class="app-panel alerts-workbench">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-list-check text-danger"></i> Riesgos activos</h3>
                    <p class="app-panel__subtitle">Filtra por tipo o busca por cohorte, responsable, etapa o comentario.</p>
                </div>
            </div>

            <div class="alerts-toolbar">
                <div class="alerts-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="alertsSearch" placeholder="Buscar riesgo..." aria-label="Buscar riesgo">
                </div>
                <div class="alerts-filter-group" aria-label="Filtrar riesgos">
                    <button type="button" class="alerts-filter is-active" data-alert-filter="all">Todos</button>
                    <button type="button" class="alerts-filter" data-alert-filter="marketing">Marketing</button>
                    <button type="button" class="alerts-filter" data-alert-filter="comment">Comentarios</button>
                </div>
            </div>

            <div class="alerts-list" id="alertsList">
                <?php foreach ($riskItems as $item): ?>
                <?php
                    $searchText = strtolower(implode(' ', [
                        $item['cohort_code'],
                        $item['cohort_name'],
                        $item['title'],
                        $item['detail'],
                        $item['body'],
                        $item['actor'],
                    ]));
                ?>
                <article class="risk-item" data-alert-item data-alert-type="<?= htmlspecialchars($item['type']) ?>" data-alert-search="<?= htmlspecialchars($searchText) ?>">
                    <div class="risk-item__icon risk-item__icon--<?= htmlspecialchars($item['tone']) ?>">
                        <i class="bi <?= htmlspecialchars($item['icon']) ?>"></i>
                    </div>
                    <div class="risk-item__body">
                        <div class="risk-item__top">
                            <div>
                                <a href="/cohorts/<?= (int) $item['cohort_id'] ?>" class="risk-item__cohort"><?= htmlspecialchars($item['cohort_code']) ?></a>
                                <span class="risk-item__name"><?= htmlspecialchars($item['cohort_name']) ?></span>
                            </div>
                            <span class="status-pill status-pill--<?= htmlspecialchars($item['tone']) ?>"><?= htmlspecialchars($item['severity']) ?></span>
                        </div>
                        <h4><?= htmlspecialchars($item['title']) ?></h4>
                        <p><?= htmlspecialchars($item['body']) ?></p>
                        <div class="risk-item__meta">
                            <?php if (!empty($item['detail_class'])): ?>
                                <span class="badge <?= htmlspecialchars($item['detail_class']) ?>"><?= htmlspecialchars($item['detail']) ?></span>
                            <?php else: ?>
                                <span><i class="bi bi-diagram-3"></i><?= htmlspecialchars($item['detail']) ?></span>
                            <?php endif; ?>
                            <span><i class="bi bi-person"></i><?= htmlspecialchars($item['actor']) ?></span>
                            <?php if (!empty($item['date'])): ?>
                                <time><i class="bi bi-clock"></i><?= date('d/m/Y H:i', strtotime($item['date'])) ?></time>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars($item['url']) ?>" class="btn btn-sm btn-outline-primary risk-item__action">
                        <?= htmlspecialchars($item['cta']) ?>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>

            <div class="alerts-empty-filter d-none" id="alertsEmptyFilter">
                <i class="bi bi-search"></i>
                <strong>Sin coincidencias</strong>
                <span>Ajusta la busqueda o cambia el tipo de riesgo.</span>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-people text-info"></i> Cohortes afectadas</h3>
                    <p class="app-panel__subtitle">Ordenadas por volumen de riesgos.</p>
                </div>
            </div>
            <div class="affected-cohort-list">
                <?php foreach (array_slice($affectedCohorts, 0, 8, true) as $cohort): ?>
                <?php $cohortTotal = (int) $cohort['marketing'] + (int) $cohort['comments']; ?>
                <a href="/cohorts/<?= (int) $cohort['id'] ?>" class="affected-cohort-item">
                    <span>
                        <strong><?= htmlspecialchars($cohort['code']) ?></strong>
                        <small><?= htmlspecialchars($cohort['name']) ?></small>
                    </span>
                    <span class="affected-cohort-count"><?= $cohortTotal ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-state-icon text-success">
                <i class="bi bi-shield-check"></i>
            </div>
            <h5 class="empty-state-title">Todo en orden</h5>
            <p class="empty-state-text">No hay alertas de riesgo activas.</p>
        </div>
    </div>
</div>
<?php endif; ?>
