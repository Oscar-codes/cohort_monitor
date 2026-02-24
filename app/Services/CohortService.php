<?php

namespace App\Services;

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
    private CohortRepository $cohortRepo;

    public function __construct()
    {
        $this->cohortRepo = new CohortRepository();
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
     * Get a single cohort by ID with calculated milestone dates.
     */
    public function getCohortById(int $id): ?array
    {
        $row = $this->cohortRepo->findById($id);
        return $row ? $this->enrichWithMilestones($row) : null;
    }

    /**
     * Get cohorts filtered by training status.
     */
    public function getCohortsByTrainingStatus(string $status): array
    {
        $rows = $this->cohortRepo->findByTrainingStatus($status);
        return array_map(fn(array $row) => $this->enrichWithMilestones($row), $rows);
    }

    // ─── Writes ──────────────────────────────────────────

    /**
     * Create a new cohort after validation.
     */
    public function createCohort(array $data): int
    {
        $this->validate($data);
        return $this->cohortRepo->create($data);
    }

    /**
     * Update an existing cohort.
     */
    public function updateCohort(int $id, array $data): bool
    {
        $this->validate($data);
        return $this->cohortRepo->update($id, $data);
    }

    /**
     * Delete a cohort.
     */
    public function deleteCohort(int $id): bool
    {
        return $this->cohortRepo->delete($id);
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

        $validStatuses = ['not_started', 'in_progress', 'completed', 'cancelled'];
        if (!empty($data['training_status']) && !in_array($data['training_status'], $validStatuses, true)) {
            throw new \InvalidArgumentException('Estado de entrenamiento no válido.');
        }
    }
}
