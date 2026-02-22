<?php

namespace App\Services;

use App\Repositories\CohortRepository;

/**
 * CohortService
 *
 * Business logic for cohort management.
 * Validates data and delegates persistence to the repository.
 */
class CohortService
{
    private CohortRepository $cohortRepo;

    public function __construct()
    {
        $this->cohortRepo = new CohortRepository();
    }

    /**
     * Get all cohorts.
     */
    public function getAllCohorts(): array
    {
        return $this->cohortRepo->findAll();
    }

    /**
     * Get a single cohort by ID.
     */
    public function getCohortById(int $id): ?array
    {
        return $this->cohortRepo->findById($id);
    }

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

    /**
     * Basic validation for cohort data.
     *
     * @throws \InvalidArgumentException
     */
    private function validate(array $data): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Cohort name is required.');
        }

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if ($data['start_date'] > $data['end_date']) {
                throw new \InvalidArgumentException('Start date must be before end date.');
            }
        }
    }
}
