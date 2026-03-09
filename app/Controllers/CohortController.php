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
        $filters = [
            'bootcamp_type'  => (string) $this->input('bootcamp_type', ''),
            'start_date'     => (string) $this->input('start_date', ''),
            'end_date'       => (string) $this->input('end_date', ''),
            'business_model' => (string) $this->input('business_model', ''),
            'cohort_status'  => (string) $this->input('cohort_status', ''),
        ];

        $cohorts = $this->cohortService->getFilteredCohorts($filters);
        $bootcampTypes = $this->cohortService->getBootcampTypes();

        // Keep only active filter values for link/query persistence.
        $activeFilters = array_filter($filters, static fn($value) => $value !== '');

        $this->view('cohorts.index', [
            'pageTitle'       => 'Cohortes',
            'activePage'      => 'cohorts',
            'cohorts'         => $cohorts,
            'filters'         => $filters,
            'activeFilters'   => $activeFilters,
            'bootcampTypes'   => $bootcampTypes,
            'canCreate'       => Auth::canCreateCohort(),
            'canDelete'       => Auth::canDeleteCohort(),
        ]);
    }

    /**
     * Show form to create a new cohort.
     * Only Admin can create.
     */
    public function create(): void
    {
        if (!Auth::canCreateCohort()) {
            http_response_code(403);
            $this->view('errors.403', ['pageTitle' => 'Acceso Denegado'], null);
            return;
        }

        $this->view('cohorts.create', [
            'pageTitle'  => 'Nueva Cohorte',
            'activePage' => 'cohorts',
        ]);
    }

    /**
     * Store a newly created cohort.
     * Only Admin can create.
     */
    public function store(): void
    {
        if (!Auth::canCreateCohort()) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permiso para crear cohortes.']);
            return;
        }

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
            'pageTitle'       => $cohort['name'],
            'activePage'      => 'cohorts',
            'cohort'          => $cohort,
            'comments'        => $comments,
            'canEdit'         => Auth::canEditCohort(),
            'canDelete'       => Auth::canDeleteCohort(),
            'editableFields'  => Auth::getEditableCohortFields(),
        ]);
    }

    /**
     * Show form to edit an existing cohort.
     */
    public function edit(string $id): void
    {
        if (!Auth::canEditCohort()) {
            http_response_code(403);
            $this->view('errors.403', ['pageTitle' => 'Acceso Denegado'], null);
            return;
        }

        $cohort = $this->cohortService->getCohortById((int) $id);

        if (!$cohort) {
            http_response_code(404);
            $this->view('errors.404', ['pageTitle' => 'Not Found'], null);
            return;
        }

        $this->view('cohorts.edit', [
            'pageTitle'       => 'Editar: ' . $cohort['name'],
            'activePage'      => 'cohorts',
            'cohort'          => $cohort,
            'editableFields'  => Auth::getEditableCohortFields(),
            'isAdmin'         => Auth::isAdmin(),
        ]);
    }

    /**
     * Update an existing cohort.
     * Each role can only update their permitted fields.
     */
    public function update(string $id): void
    {
        if (!Auth::canEditCohort()) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permiso para editar cohortes.']);
            return;
        }

        $allData = $this->collectFormData();
        
        // Filter to only editable fields for this role (backend validation)
        $filteredData = Auth::filterEditableCohortData($allData);
        
        if (empty($filteredData)) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permiso para modificar estos campos.']);
            return;
        }

        $this->cohortService->updateCohortPartial((int) $id, $filteredData);
        $this->redirect('/cohorts/' . $id);
    }

    /**
     * Delete a cohort.
     * Only Admin can delete.
     */
    public function destroy(string $id): void
    {
        if (!Auth::canDeleteCohort()) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permiso para eliminar cohortes.']);
            return;
        }

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
            'cohort_code'              => $this->normalizeTextInput($this->input('cohort_code')),
            'name'                     => $this->normalizeTextInput($this->input('name')),
            'correlative_number'       => (int) $this->input('correlative_number', '0'),
            'total_admission_target'   => (int) $this->input('total_admission_target', '0'),
            'b2b_admission_target'     => (int) $this->input('b2b_admission_target', '0'),
            'b2b_admissions'           => (int) $this->input('b2b_admissions', '0'),
            'b2c_admissions'           => (int) $this->input('b2c_admissions', '0'),
            'admission_deadline_date'  => $this->input('admission_deadline_date') ?: null,
            'start_date'               => $this->input('start_date') ?: null,
            'end_date'                 => $this->input('end_date') ?: null,
            'related_project'          => $this->normalizeTextInput($this->input('related_project')),
            'assigned_coach'           => $this->normalizeTextInput($this->input('assigned_coach')),
            'bootcamp_type'            => $this->normalizeTextInput($this->input('bootcamp_type')),
            'area'                     => $this->input('area') ?: null,
            'assigned_class_schedule'  => $this->normalizeTextInput($this->input('assigned_class_schedule')),
            'training_status'          => $this->input('training_status', 'not_started'),
        ];
    }

    /**
     * Normalize text input to UTF-8 and repair common mojibake sequences.
     */
    private function normalizeTextInput(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (function_exists('mb_convert_encoding')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        $replacements = [
            'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú',
            'Ã' => 'Á', 'Ã‰' => 'É', 'Ã' => 'Í', 'Ã“' => 'Ó', 'Ãš' => 'Ú',
            'Ã±' => 'ñ', 'Ã‘' => 'Ñ',
            'Â' => '',
        ];

        return strtr($text, $replacements);
    }
}
