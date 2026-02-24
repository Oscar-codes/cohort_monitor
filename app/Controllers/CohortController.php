<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\CohortService;
use App\Services\AlertService;

/**
 * CohortController
 *
 * Manages CRUD operations for cohorts.
 * Delegates business logic to CohortService.
 * Auth-protected: all users may view, but edit/create/delete depends on role.
 */
class CohortController extends Controller
{
    private CohortService $cohortService;
    private AlertService  $alertService;

    public function __construct()
    {
        Auth::requireLogin();
        $this->cohortService = new CohortService();
        $this->alertService  = new AlertService();
    }

    /**
     * List all cohorts.
     */
    public function index(): void
    {
        $cohorts = $this->cohortService->getAllCohorts();

        $this->view('cohorts.index', [
            'pageTitle'  => 'Cohortes',
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
            'pageTitle'  => 'Nueva Cohorte',
            'activePage' => 'cohorts',
        ]);
    }

    /**
     * Store a newly created cohort.
     */
    public function store(): void
    {
        $data = $this->collectFormData();
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

        $comments = $this->alertService->getCommentsForCohort((int) $id);

        $this->view('cohorts.show', [
            'pageTitle'  => $cohort['name'],
            'activePage' => 'cohorts',
            'cohort'     => $cohort,
            'comments'   => $comments,
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
            'pageTitle'  => 'Editar: ' . $cohort['name'],
            'activePage' => 'cohorts',
            'cohort'     => $cohort,
        ]);
    }

    /**
     * Update an existing cohort.
     */
    public function update(string $id): void
    {
        $data = $this->collectFormData();
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

    // ─── Private helpers ─────────────────────────────────

    /**
     * Collect all cohort form fields from the request.
     */
    private function collectFormData(): array
    {
        return [
            'cohort_code'              => $this->input('cohort_code'),
            'name'                     => $this->input('name'),
            'correlative_number'       => (int) $this->input('correlative_number', '0'),
            'total_admission_target'   => (int) $this->input('total_admission_target', '0'),
            'b2b_admission_target'     => (int) $this->input('b2b_admission_target', '0'),
            'b2c_admissions'           => (int) $this->input('b2c_admissions', '0'),
            'admission_deadline_date'  => $this->input('admission_deadline_date') ?: null,
            'start_date'               => $this->input('start_date') ?: null,
            'end_date'                 => $this->input('end_date') ?: null,
            'related_project'          => $this->input('related_project') ?: null,
            'assigned_coach'           => $this->input('assigned_coach') ?: null,
            'bootcamp_type'            => $this->input('bootcamp_type') ?: null,
            'assigned_class_schedule'  => $this->input('assigned_class_schedule') ?: null,
            'training_status'          => $this->input('training_status', 'not_started'),
        ];
    }
}
