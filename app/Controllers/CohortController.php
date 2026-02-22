<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CohortService;

/**
 * CohortController
 *
 * Manages CRUD operations for cohorts.
 * Delegates business logic to CohortService.
 */
class CohortController extends Controller
{
    private CohortService $cohortService;

    public function __construct()
    {
        $this->cohortService = new CohortService();
    }

    /**
     * List all cohorts.
     */
    public function index(): void
    {
        $cohorts = $this->cohortService->getAllCohorts();

        $this->view('cohorts.index', [
            'pageTitle'  => 'Cohorts',
            'activePage' => 'cohorts',
            'cohorts'    => $cohorts,
        ]);
    }

    /**
     * Show form to create a new cohort.
     */
    public function create(): void
    {
        $this->view('cohorts.create', [
            'pageTitle'  => 'Create Cohort',
            'activePage' => 'cohorts',
        ]);
    }

    /**
     * Store a newly created cohort.
     */
    public function store(): void
    {
        $data = [
            'name'        => $this->input('name'),
            'description' => $this->input('description'),
            'start_date'  => $this->input('start_date'),
            'end_date'    => $this->input('end_date'),
            'status'      => $this->input('status', 'active'),
        ];

        $this->cohortService->createCohort($data);
        $this->redirect('/cohorts');
    }

    /**
     * Display a single cohort.
     */
    public function show(string $id): void
    {
        $cohort = $this->cohortService->getCohortById((int) $id);

        if (!$cohort) {
            http_response_code(404);
            $this->view('errors.404', ['pageTitle' => 'Not Found'], null);
            return;
        }

        $this->view('cohorts.show', [
            'pageTitle'  => $cohort['name'],
            'activePage' => 'cohorts',
            'cohort'     => $cohort,
        ]);
    }

    /**
     * Show form to edit an existing cohort.
     */
    public function edit(string $id): void
    {
        $cohort = $this->cohortService->getCohortById((int) $id);

        if (!$cohort) {
            http_response_code(404);
            $this->view('errors.404', ['pageTitle' => 'Not Found'], null);
            return;
        }

        $this->view('cohorts.edit', [
            'pageTitle'  => 'Edit: ' . $cohort['name'],
            'activePage' => 'cohorts',
            'cohort'     => $cohort,
        ]);
    }

    /**
     * Update an existing cohort.
     */
    public function update(string $id): void
    {
        $data = [
            'name'        => $this->input('name'),
            'description' => $this->input('description'),
            'start_date'  => $this->input('start_date'),
            'end_date'    => $this->input('end_date'),
            'status'      => $this->input('status'),
        ];

        $this->cohortService->updateCohort((int) $id, $data);
        $this->redirect('/cohorts/' . $id);
    }

    /**
     * Delete a cohort.
     */
    public function destroy(string $id): void
    {
        $this->cohortService->deleteCohort((int) $id);
        $this->redirect('/cohorts');
    }
}
