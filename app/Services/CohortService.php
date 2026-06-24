<?php

namespace App\Services;

use App\Core\Auth;
use App\Repositories\AuditRepository;
use App\Repositories\CohortRepository;
use DateTime;

/**
 * CohortService
 *
 * Business logic for cohort management.
 * Validates data and delegates persistence to the repository.
 * Computes calculated fields (50 % / 75 % training dates).
 */
class CohortService
{
    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING_RESCHEDULE = 'pending_reschedule';

    public const STATUS_LABELS = [
        self::STATUS_PLANNED => 'Planificado',
        self::STATUS_IN_PROGRESS => 'En progreso',
        self::STATUS_COMPLETED => 'Completado',
        self::STATUS_CANCELLED => 'Cancelado',
        self::STATUS_PENDING_RESCHEDULE => 'Pendiente de reprogramar',
    ];

    private const DELETABLE_STATUSES = [
        self::STATUS_PLANNED,
        self::STATUS_CANCELLED,
        self::STATUS_PENDING_RESCHEDULE,
    ];

    private const WORKFLOW_TRANSITIONS = [
        self::STATUS_PLANNED => [
            self::STATUS_CANCELLED,
            self::STATUS_PENDING_RESCHEDULE,
            self::STATUS_COMPLETED,
        ],
        self::STATUS_IN_PROGRESS => [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [
            self::STATUS_PLANNED,
            self::STATUS_PENDING_RESCHEDULE,
        ],
        self::STATUS_PENDING_RESCHEDULE => [
            self::STATUS_PLANNED,
            self::STATUS_CANCELLED,
        ],
    ];

    private CohortRepository $cohortRepo;
    private AuditRepository $auditRepo;

    public function __construct()
    {
        $this->cohortRepo = new CohortRepository();
        $this->auditRepo = new AuditRepository();
    }

    // ─── Reads ───────────────────────────────────────────

    /**
     * Get all cohorts with calculated milestone dates.
     */
    public function getAllCohorts(): array
    {
        $rows = $this->cohortRepo->findAll();
        return array_map(fn(array $row) => $this->enrichWithMilestones($row), $rows);
    }

    /**
     * Get cohorts using combinable filters.
     */
    public function getFilteredCohorts(array $filters): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $rows = $this->cohortRepo->findByFilters($normalizedFilters);

        return array_map(fn(array $row) => $this->enrichWithMilestones($row), $rows);
    }

    /**
     * Get all available bootcamp types for filter dropdown.
     *
     * @return string[]
     */
    public function getBootcampTypes(): array
    {
        return $this->cohortRepo->findBootcampTypes();
    }

    /**
     * Get distinct project names for filtering.
     */
    public function getProjectNames(): array
    {
        return $this->cohortRepo->findProjectNames();
    }

    /**
     * Financial aggregation by month (filtered).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFinancialByMonth(array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        return $this->cohortRepo->getFinancialByMonth($normalizedFilters);
    }

    /**
     * Financial aggregation by bootcamp (filtered).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFinancialByBootcamp(array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        return $this->cohortRepo->getFinancialByBootcamp($normalizedFilters);
    }

    /**
     * Get a single cohort by ID with calculated milestone dates.
     */
    public function getCohortById(int $id): ?array
    {
        $row = $this->cohortRepo->findById($id);
        return $row ? $this->enrichWithMilestones($row) : null;
    }

    /**
     * Get allowed workflow transitions for a cohort.
     *
     * @return string[]
     */
    public function getAllowedStatusTransitions(array $cohort): array
    {
        $currentStatus = $this->effectiveTrainingStatus($cohort);
        return self::WORKFLOW_TRANSITIONS[$currentStatus] ?? [];
    }

    // ─── Writes ──────────────────────────────────────────

    /**
     * Create a new cohort after validation.
     */
    public function createCohort(array $data): int
    {
        $data = $this->normalizeCohortData($data);
        $data['training_status'] = self::STATUS_PLANNED;
        $this->validate($data);
        return $this->cohortRepo->create($data);
    }

    /**
     * Update an existing cohort.
     */
    public function updateCohort(int $id, array $data): bool
    {
        $this->assertStatusIsNotManuallyEditable($data);
        $data = $this->normalizeCohortData($data);
        $this->validate($data);
        return $this->cohortRepo->update($id, $data);
    }

    /**
     * Update only specific fields of a cohort (partial update).
     * Used when non-admin users edit only their permitted fields.
     * Skips full validation since only specific fields are being updated.
     */
    public function updateCohortPartial(int $id, array $data): bool
    {
        $this->assertStatusIsNotManuallyEditable($data);

        $old = $this->getCohortById($id);

        // Validate only the fields that are being updated
        $data = $this->normalizeCohortData($data);
        $this->validatePartial($data);
        $updated = $this->cohortRepo->updatePartial($id, $data);

        if ($updated && $old !== null) {
            $this->auditPartialChanges($old, $data);
        }

        return $updated;
    }

    /**
     * Delete a cohort.
     */
    public function deleteCohort(int $id): bool
    {
        $cohort = $this->getCohortById($id);
        if (!$cohort) {
            return false;
        }

        $effectiveStatus = $this->effectiveTrainingStatus($cohort);
        if (!in_array($effectiveStatus, self::DELETABLE_STATUSES, true)) {
            throw new \InvalidArgumentException('No se pueden eliminar cohortes En progreso o Completadas.');
        }

        return $this->cohortRepo->delete($id);
    }

    public function transitionCohortStatus(int $id, string $targetStatus, ?string $reason = null): bool
    {
        $cohort = $this->getCohortById($id);
        if (!$cohort) {
            throw new \InvalidArgumentException('Cohorte no encontrada.');
        }

        $targetStatus = $this->normalizeTrainingStatus($targetStatus);
        $currentStatus = $this->effectiveTrainingStatus($cohort);
        $allowedTransitions = $this->getAllowedStatusTransitions($cohort);
        $reason = trim((string) ($reason ?? ''));

        if (!isset(self::STATUS_LABELS[$targetStatus])) {
            throw new \InvalidArgumentException('Estado de workflow no válido.');
        }

        if ($currentStatus === $targetStatus) {
            throw new \InvalidArgumentException('La cohorte ya se encuentra en ese estado.');
        }

        if (!in_array($targetStatus, $allowedTransitions, true)) {
            throw new \InvalidArgumentException('La transición de estado solicitada no está permitida para esta cohorte.');
        }

        if (in_array($targetStatus, [self::STATUS_CANCELLED, self::STATUS_PENDING_RESCHEDULE], true) && $reason === '') {
            throw new \InvalidArgumentException('Debes ingresar un motivo para cancelar o reprogramar la cohorte.');
        }

        if ($targetStatus === self::STATUS_PLANNED) {
            $this->assertPlanningDataReadyForPlanned($cohort);
        }

        $updated = $this->cohortRepo->updatePartial($id, ['training_status' => $targetStatus]);

        if ($updated) {
            $this->auditRepo->log([
                'user_id'     => Auth::id(),
                'action'      => 'transition_cohort_status',
                'entity_type' => 'cohort',
                'entity_id'   => $id,
                'old_values'  => [
                    'effective_status' => $currentStatus,
                    'stored_status' => $this->normalizeTrainingStatus((string) ($cohort['training_status'] ?? self::STATUS_PLANNED)),
                ],
                'new_values'  => [
                    'training_status' => $targetStatus,
                    'reason' => $reason !== '' ? $reason : null,
                ],
            ]);
        }

        return $updated;
    }

    public function effectiveTrainingStatus(array $cohort): string
    {
        $status = $this->normalizeTrainingStatus((string) ($cohort['training_status'] ?? self::STATUS_PLANNED));

        if (in_array($status, [self::STATUS_CANCELLED, self::STATUS_PENDING_RESCHEDULE, self::STATUS_COMPLETED], true)) {
            return $status;
        }

        $today = date('Y-m-d');
        $startDate = $cohort['start_date'] ?? null;
        $endDate = $cohort['end_date'] ?? null;

        if ($endDate && $endDate < $today) {
            return self::STATUS_COMPLETED;
        }

        if ($startDate && $startDate <= $today && (!$endDate || $endDate >= $today)) {
            return self::STATUS_IN_PROGRESS;
        }

        return self::STATUS_PLANNED;
    }

    public function normalizeTrainingStatus(string $status): string
    {
        return match ($status) {
            'not_started', 'upcoming', 'pending', 'pendiente', 'planificado' => self::STATUS_PLANNED,
            'in_progress', 'en progreso', 'en ejecucion', 'en ejecución' => self::STATUS_IN_PROGRESS,
            'completed', 'completado' => self::STATUS_COMPLETED,
            'cancelled', 'cancelado' => self::STATUS_CANCELLED,
            'pending_reschedule', 'pendiente de reprogramar' => self::STATUS_PENDING_RESCHEDULE,
            default => $status,
        };
    }

    // ─── Calculated fields ───────────────────────────────

    /**
     * Calculate the 50 % and 75 % milestone training dates.
     *
     * The calculation is based on the total calendar days
     * between start_date and end_date:
     *   • 50 % = start_date + 50 % of total days
     *   • 75 % = start_date + 75 % of total days
     *
     * @return array{training_date_50: string|null, training_date_75: string|null}
     */
    public function calculateTrainingMilestones(?string $startDate, ?string $endDate): array
    {
        if (empty($startDate) || empty($endDate)) {
            return ['training_date_50' => null, 'training_date_75' => null];
        }

        try {
            $start = new DateTime($startDate);
            $end   = new DateTime($endDate);
            $totalDays = (int) $start->diff($end)->days;

            if ($totalDays <= 0) {
                return ['training_date_50' => null, 'training_date_75' => null];
            }

            $days50 = (int) round($totalDays * 0.50);
            $days75 = (int) round($totalDays * 0.75);

            $date50 = (clone $start)->modify("+{$days50} days")->format('Y-m-d');
            $date75 = (clone $start)->modify("+{$days75} days")->format('Y-m-d');

            return [
                'training_date_50' => $date50,
                'training_date_75' => $date75,
            ];
        } catch (\Exception) {
            return ['training_date_50' => null, 'training_date_75' => null];
        }
    }

    // ─── Private helpers ─────────────────────────────────

    /**
     * Enrich a cohort row with calculated milestone dates.
     */
    private function enrichWithMilestones(array $row): array
    {
        $milestones = $this->calculateTrainingMilestones(
            $row['start_date'] ?? null,
            $row['end_date'] ?? null,
        );

        return array_merge($row, $milestones);
    }

    private function normalizeCohortData(array $data): array
    {
        if (array_key_exists('training_status', $data) && !empty($data['training_status'])) {
            $data['training_status'] = $this->normalizeTrainingStatus((string) $data['training_status']);
        }

        return $data;
    }

    /**
     * Validate cohort data before create / update.
     *
     * @throws \InvalidArgumentException
     */
    private function validate(array $data): void
    {
        if (empty($data['cohort_code'])) {
            throw new \InvalidArgumentException('El código de cohorte es obligatorio.');
        }

        if (empty($data['name'])) {
            throw new \InvalidArgumentException('El nombre de la cohorte es obligatorio.');
        }

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if ($data['start_date'] > $data['end_date']) {
                throw new \InvalidArgumentException('La fecha de inicio debe ser anterior a la fecha de fin.');
            }
        }

        if (!empty($data['training_status'])) {
            $data['training_status'] = $this->normalizeTrainingStatus((string) $data['training_status']);
        }

        $validStatuses = array_keys(self::STATUS_LABELS);
        if (!empty($data['training_status']) && !in_array($data['training_status'], $validStatuses, true)) {
            throw new \InvalidArgumentException('Estado de entrenamiento no válido.');
        }

        $this->validatePartial($data);
    }

    /**
     * Validate partial cohort data (for role-limited updates).
     * Only validates the fields that are present in the data array.
     *
     * @throws \InvalidArgumentException
     */
    private function validatePartial(array $data): void
    {
        // Validate numeric admission fields if present
        $numericFields = [
            'total_admission_target',
            'b2b_admission_target',
            'b2c_admission_target',
            'b2b_admissions',
            'b2c_admissions',
            'correlative_number',
        ];

        foreach ($numericFields as $field) {
            if (array_key_exists($field, $data)) {
                if (!$this->isNonNegativeInteger($data[$field])) {
                    throw new \InvalidArgumentException("El campo {$field} debe ser un número positivo.");
                }
            }
        }

        $revenueFields = ['financial_target_revenue', 'financial_actual_revenue'];
        foreach ($revenueFields as $field) {
            if (array_key_exists($field, $data)) {
                if (!$this->isNonNegativeDecimal($data[$field])) {
                    throw new \InvalidArgumentException("El campo {$field} debe ser un monto positivo.");
                }
            }
        }

        // Validate date fields if present
        $dateFields = ['start_date', 'end_date', 'admission_deadline_date'];
        foreach ($dateFields as $field) {
            if (!empty($data[$field])) {
                $date = DateTime::createFromFormat('Y-m-d', $data[$field]);
                if (!$date) {
                    throw new \InvalidArgumentException("El campo {$field} debe ser una fecha válida (YYYY-MM-DD).");
                }
            }
        }

        // Validate training status if present
        if (array_key_exists('training_status', $data) && !empty($data['training_status'])) {
            $status = $this->normalizeTrainingStatus((string) $data['training_status']);
            $validStatuses = array_keys(self::STATUS_LABELS);
            if (!in_array($status, $validStatuses, true)) {
                throw new \InvalidArgumentException('Estado de entrenamiento no válido.');
            }
        }
    }

    /**
     * Normalize and whitelist filter values accepted by repository queries.
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = [
            'search'          => null,
            'bootcamp_type'   => null,
            'related_project' => null,
            'start_date'      => null,
            'end_date'        => null,
            'business_model'  => null,
            'cohort_status'   => null,
        ];

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            if ($search !== '') {
                $search = (string) preg_replace('/\s+/u', ' ', $search);
                $normalized['search'] = function_exists('mb_substr') ? mb_substr($search, 0, 80) : substr($search, 0, 80);
            }
        }

        if (!empty($filters['bootcamp_type'])) {
            $normalized['bootcamp_type'] = trim((string) $filters['bootcamp_type']);
        }

        if (!empty($filters['related_project'])) {
            $normalized['related_project'] = trim((string) $filters['related_project']);
        }

        // Validate date format (YYYY-MM-DD) before passing to repository
        if (!empty($filters['start_date'])) {
            $date = DateTime::createFromFormat('Y-m-d', (string) $filters['start_date']);
            if ($date && $date->format('Y-m-d') === (string) $filters['start_date']) {
                $normalized['start_date'] = (string) $filters['start_date'];
            }
        }

        if (!empty($filters['end_date'])) {
            $date = DateTime::createFromFormat('Y-m-d', (string) $filters['end_date']);
            if ($date && $date->format('Y-m-d') === (string) $filters['end_date']) {
                $normalized['end_date'] = (string) $filters['end_date'];
            }
        }

        // Cross-validate: if both dates are valid but start > end, swap them
        if ($normalized['start_date'] && $normalized['end_date'] && $normalized['start_date'] > $normalized['end_date']) {
            $temp = $normalized['start_date'];
            $normalized['start_date'] = $normalized['end_date'];
            $normalized['end_date'] = $temp;
        }

        $validBusinessModels = ['b2b', 'b2c'];
        if (!empty($filters['business_model']) && in_array($filters['business_model'], $validBusinessModels, true)) {
            $normalized['business_model'] = $filters['business_model'];
        }

        $validStatuses = [
            self::STATUS_PLANNED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_PENDING_RESCHEDULE,
            'upcoming',
        ];
        if (!empty($filters['cohort_status']) && in_array($filters['cohort_status'], $validStatuses, true)) {
            $normalized['cohort_status'] = $this->normalizeTrainingStatus((string) $filters['cohort_status']);
        }

        return $normalized;
    }

    private function assertStatusIsNotManuallyEditable(array $data): void
    {
        if (array_key_exists('training_status', $data)) {
            throw new \InvalidArgumentException('El estado de la cohorte no se puede editar libremente. Usa una accion controlada del workflow.');
        }
    }

    private function assertPlanningDataReadyForPlanned(array $cohort): void
    {
        $requiredFields = [
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de fin',
            'assigned_coach' => 'coach asignado',
            'assigned_class_schedule' => 'horario asignado',
        ];

        $missing = [];
        foreach ($requiredFields as $field => $label) {
            $value = trim((string) ($cohort[$field] ?? ''));
            if ($value === '') {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            throw new \InvalidArgumentException('Antes de volver a Planificado debes completar: ' . implode(', ', $missing) . '.');
        }
    }

    private function isNonNegativeInteger(mixed $value): bool
    {
        $value = trim((string) $value);
        return $value !== '' && preg_match('/^\d+$/', $value) === 1;
    }

    private function isNonNegativeDecimal(mixed $value): bool
    {
        $value = trim((string) $value);
        return $value !== '' && preg_match('/^\d+(\.\d{1,2})?$/', $value) === 1;
    }

    private function auditPartialChanges(array $old, array $new): void
    {
        $fields = [
            'financial_target_revenue' => 'Meta ingresos',
            'financial_actual_revenue' => 'Ingreso actual',
            'b2b_admissions' => 'Inscritos B2B',
            'b2c_admissions' => 'Inscritos B2C',
            'b2b_admission_target' => 'Meta B2B',
            'b2c_admission_target' => 'Meta B2C',
            'total_admission_target' => 'Meta a inscribir',
        ];

        $oldValues = [];
        $newValues = [];
        foreach ($fields as $key => $label) {
            if (array_key_exists($key, $new) && (string) ($old[$key] ?? '') !== (string) $new[$key]) {
                $oldValues[$label] = $old[$key] ?? null;
                $newValues[$label] = $new[$key] ?? null;
            }
        }

        if ($oldValues === []) {
            return;
        }

        $this->auditRepo->log([
            'user_id'     => Auth::id(),
            'action'      => 'update_cohort_partial',
            'entity_type' => 'cohort',
            'entity_id'   => (int) ($old['id'] ?? 0),
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
        ]);
    }
}
