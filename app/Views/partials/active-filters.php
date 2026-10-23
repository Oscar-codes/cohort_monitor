<?php
/**
 * Active filter chips, shared by Cohortes and Plan Maestro (DATA-003), so any
 * difference between screens is explained by a filter visible on screen.
 *
 * @var array<string, string> $activeFilters      non-empty filter values
 * @var string|null           $emptyFiltersText   text shown when there are no filters (optional)
 */
$activeFilters = isset($activeFilters) && is_array($activeFilters) ? $activeFilters : [];
$emptyFiltersText = isset($emptyFiltersText) ? (string) $emptyFiltersText : '';

$chipLabels = [
    'search'          => 'Búsqueda',
    'bootcamp_type'   => 'Bootcamp',
    'related_project' => 'Proyecto',
    'start_date'      => 'Desde',
    'end_date'        => 'Hasta',
    'business_model'  => 'Población',
    'cohort_status'   => 'Estado',
];
$chipValueFor = static function (string $key, string $value): string {
    return match ($key) {
        'cohort_status'  => \App\Services\CohortStatus::label($value),
        'business_model' => strtoupper($value),
        default          => $value,
    };
};
?>
<?php if (!empty($activeFilters)): ?>
<div class="active-filter-row" data-active-filters="<?= count($activeFilters) ?>">
    <span class="active-filter-label">Filtros activos</span>
    <?php foreach ($activeFilters as $chipKey => $chipValue): ?>
        <span class="active-filter-chip">
            <?= htmlspecialchars($chipLabels[$chipKey] ?? str_replace('_', ' ', (string) $chipKey)) ?>:
            <strong><?= htmlspecialchars($chipValueFor((string) $chipKey, (string) $chipValue)) ?></strong>
        </span>
    <?php endforeach; ?>
</div>
<?php elseif ($emptyFiltersText !== ''): ?>
<div class="active-filter-row" data-active-filters="0">
    <span class="active-filter-label"><?= htmlspecialchars($emptyFiltersText) ?></span>
</div>
<?php endif; ?>
