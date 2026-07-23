<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\CohortService;
use App\Services\AlertService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        Auth::requireAccess('cohorts');
        $filters = [
            'search'          => (string) $this->input('search', ''),
            'bootcamp_type'   => (string) $this->input('bootcamp_type', ''),
            'related_project' => (string) $this->input('related_project', ''),
            'start_date'      => (string) $this->input('start_date', ''),
            'end_date'        => (string) $this->input('end_date', ''),
            'business_model'  => (string) $this->input('business_model', ''),
            'cohort_status'   => (string) $this->input('cohort_status', ''),
        ];

        $cohorts = $this->cohortService->getFilteredCohorts($filters);
        $bootcampTypes = $this->cohortService->getBootcampTypes();
        $projectNames = $this->cohortService->getProjectNames();

        // Check if dates were swapped and show a warning
        if (!empty($filters['start_date']) && !empty($filters['end_date']) && $filters['start_date'] > $filters['end_date']) {
            Auth::flash('info', 'Las fechas estaban invertidas y fueron ajustadas automáticamente.');
        }

        // Keep only active filter values for link/query persistence.
        $activeFilters = array_filter($filters, static fn($value) => $value !== '');

        $this->view('cohorts.index', [
            'pageTitle'       => 'Cohortes',
            'activePage'      => 'cohorts',
            'cohorts'         => $cohorts,
            'filters'         => $filters,
            'activeFilters'   => $activeFilters,
            'bootcampTypes'   => $bootcampTypes,
            'projectNames'    => $projectNames,
            'canCreate'       => Auth::canCreateCohort(),
            'canEdit'         => Auth::canEditCohort(),
            'canDelete'       => Auth::canDeleteCohort(),
            'scripts'         => [
                '/assets/js/cohorts-index.js',
            ],
        ]);
    }

    /**
     * Master cohort plan view with full operational visibility.
     */
    public function master(): void
    {
        Auth::requireAccess('cohorts_master');
        $filters = [
            'search'          => (string) $this->input('search', ''),
            'bootcamp_type'   => (string) $this->input('bootcamp_type', ''),
            'related_project' => (string) $this->input('related_project', ''),
            'start_date'      => (string) $this->input('start_date', ''),
            'end_date'        => (string) $this->input('end_date', ''),
            'business_model'  => (string) $this->input('business_model', ''),
            'cohort_status'   => (string) $this->input('cohort_status', ''),
        ];

        $cohorts = $this->cohortService->getFilteredCohorts($filters);
        $bootcampTypes = $this->cohortService->getBootcampTypes();
        $projectNames = $this->cohortService->getProjectNames();
        
        // Check if dates were swapped and show a warning
        if (!empty($filters['start_date']) && !empty($filters['end_date']) && $filters['start_date'] > $filters['end_date']) {
            Auth::flash('info', 'Las fechas estaban invertidas y fueron ajustadas automáticamente.');
        }
        
        $activeFilters = array_filter($filters, static fn($value) => $value !== '');

        $totalRows = count($cohorts);
        $totalAdmissionsTarget = 0;
        $totalAdmissionsActual = 0;
        $totalRevenueTarget = 0.0;
        $totalRevenueActual = 0.0;
        $atRiskAdmissionsCount = 0;

        foreach ($cohorts as $cohort) {
            $target = max(0, (int) ($cohort['total_admission_target'] ?? 0));
            $actual = max(0, (int) ($cohort['b2b_admissions'] ?? 0)) + max(0, (int) ($cohort['b2c_admissions'] ?? 0));

            $totalAdmissionsTarget += $target;
            $totalAdmissionsActual += $actual;
            $totalRevenueTarget += max(0.0, (float) ($cohort['financial_target_revenue'] ?? 0));
            $totalRevenueActual += max(0.0, (float) ($cohort['financial_actual_revenue'] ?? 0));

            if ($target > 0 && $actual < (int) floor($target * 0.7)) {
                $atRiskAdmissionsCount++;
            }
        }

        $this->view('cohorts.master', [
            'pageTitle'              => 'Plan Maestro Cohort',
            'activePage'             => 'cohorts-master',
            'cohorts'                => $cohorts,
            'filters'                => $filters,
            'activeFilters'          => $activeFilters,
            'bootcampTypes'          => $bootcampTypes,
            'projectNames'           => $projectNames,
            'canCreate'              => Auth::canCreateCohort(),
            'canEdit'                => Auth::canEditCohort(),
            'canDelete'              => Auth::canDeleteCohort(),
            'totalRows'              => $totalRows,
            'totalAdmissionsTarget'  => $totalAdmissionsTarget,
            'totalAdmissionsActual'  => $totalAdmissionsActual,
            'totalRevenueTarget'     => $totalRevenueTarget,
            'totalRevenueActual'     => $totalRevenueActual,
            'atRiskAdmissionsCount'  => $atRiskAdmissionsCount,
        ]);
    }

    /**
     * Financial dashboard with month and bootcamp breakdown.
     */
    public function finance(): void
    {
        Auth::requireAccess('cohorts_finance');
        $chartPrefs = $this->resolveFinanceChartPreferences();

        $defaultFilters = $this->financeFilterDefaults();
        $filterKeys = array_keys($defaultFilters);
        $requestHasFilterParams = false;
        foreach ($filterKeys as $key) {
            if (array_key_exists($key, $_GET)) {
                $requestHasFilterParams = true;
                break;
            }
        }

        if ((string) $this->input('reset_filters', '') === '1') {
            unset($_SESSION['finance_filters']);
            $filters = $defaultFilters;
        } elseif ($requestHasFilterParams) {
            $filters = [
                'search'          => (string) $this->input('search', ''),
                'bootcamp_type'   => (string) $this->input('bootcamp_type', ''),
                'related_project' => (string) $this->input('related_project', ''),
                'start_date'      => (string) $this->input('start_date', ''),
                'end_date'        => (string) $this->input('end_date', ''),
                'business_model'  => (string) $this->input('business_model', ''),
                'cohort_status'   => (string) $this->input('cohort_status', ''),
            ];
            $_SESSION['finance_filters'] = $filters;
        } else {
            $sessionFilters = $_SESSION['finance_filters'] ?? [];
            $filters = [
                'search'          => (string) ($sessionFilters['search'] ?? ''),
                'bootcamp_type'   => (string) ($sessionFilters['bootcamp_type'] ?? ''),
                'related_project' => (string) ($sessionFilters['related_project'] ?? ''),
                'start_date'      => (string) ($sessionFilters['start_date'] ?? ''),
                'end_date'        => (string) ($sessionFilters['end_date'] ?? ''),
                'business_model'  => (string) ($sessionFilters['business_model'] ?? ''),
                'cohort_status'   => (string) ($sessionFilters['cohort_status'] ?? ''),
            ];
            $_SESSION['finance_filters'] = $filters;
        }

        $bootcampTypes = $this->cohortService->getBootcampTypes();
        $projectNames = $this->cohortService->getProjectNames();
        
        // Check if dates were swapped and show a warning
        if (!empty($filters['start_date']) && !empty($filters['end_date']) && $filters['start_date'] > $filters['end_date']) {
            Auth::flash('info', 'Las fechas estaban invertidas y fueron ajustadas automáticamente.');
        }
        
        $activeFilters = array_filter($filters, static fn($value) => $value !== '');

        $byMonth = $this->cohortService->getFinancialByMonth($filters);
        $byBootcamp = $this->cohortService->getFinancialByBootcamp($filters);

        $totalTarget = 0.0;
        $totalActual = 0.0;
        foreach ($byMonth as $row) {
            $totalTarget += max(0.0, (float) ($row['target_revenue'] ?? 0));
            $totalActual += max(0.0, (float) ($row['actual_revenue'] ?? 0));
        }

        $financeChartData = [
            'monthly' => [
                'labels' => array_values(array_map(
                    static fn(array $row): string => (string) ($row['period_label'] ?? '—'),
                    $byMonth
                )),
                'target' => array_values(array_map(
                    static fn(array $row): float => (float) ($row['target_revenue'] ?? 0),
                    $byMonth
                )),
                'actual' => array_values(array_map(
                    static fn(array $row): float => (float) ($row['actual_revenue'] ?? 0),
                    $byMonth
                )),
            ],
            'bootcamp' => [
                'labels' => array_values(array_map(
                    static fn(array $row): string => (string) ($row['bootcamp_name'] ?? '—'),
                    $byBootcamp
                )),
                'target' => array_values(array_map(
                    static fn(array $row): float => (float) ($row['target_revenue'] ?? 0),
                    $byBootcamp
                )),
                'actual' => array_values(array_map(
                    static fn(array $row): float => (float) ($row['actual_revenue'] ?? 0),
                    $byBootcamp
                )),
            ],
            'preferences' => $chartPrefs,
        ];

        $this->view('cohorts.finance', [
            'pageTitle'      => 'Finanzas Cohort Plan',
            'activePage'     => 'cohorts-finance',
            'filters'        => $filters,
            'activeFilters'  => $activeFilters,
            'bootcampTypes'  => $bootcampTypes,
            'projectNames'   => $projectNames,
            'byMonth'        => $byMonth,
            'byBootcamp'     => $byBootcamp,
            'totalTarget'    => $totalTarget,
            'totalActual'    => $totalActual,
            'chartPrefs'     => $chartPrefs,
            'financeChartData' => $financeChartData,
            'styles' => [
                '/assets/vendor/apexcharts/apexcharts.css',
            ],
            'scripts' => [
                '/assets/vendor/apexcharts/apexcharts.min.js',
                '/assets/js/cohorts-finance.js',
            ],
        ]);
    }

    /**
     * Persist finance chart preferences in session for the current user.
     */
    public function updateFinancePreferences(): void
    {
        Auth::requireAccess('cohorts_finance');
        $prefs = $_SESSION['finance_chart_preferences'] ?? [
            'top_n' => 10,
            'forecast_horizon' => 3,
            'forecast_method' => 'moving_avg',
        ];

        $prefs['top_n'] = $this->sanitizeTopN($_POST['top_n'] ?? ($prefs['top_n'] ?? 10));
        $prefs['forecast_horizon'] = $this->sanitizeForecastHorizon($_POST['forecast_horizon'] ?? ($prefs['forecast_horizon'] ?? 3));
        $prefs['forecast_method'] = $this->sanitizeForecastMethod($_POST['forecast_method'] ?? ($prefs['forecast_method'] ?? 'moving_avg'));

        $_SESSION['finance_chart_preferences'] = $prefs;

        $this->json([
            'ok' => true,
            'preferences' => $prefs,
        ]);
    }

    /**
     * Resolve and normalize chart preferences, allowing URL overrides.
     */
    private function resolveFinanceChartPreferences(): array
    {
        $prefs = $_SESSION['finance_chart_preferences'] ?? [
            'top_n' => 10,
            'forecast_horizon' => 3,
            'forecast_method' => 'moving_avg',
        ];

        $prefs['top_n'] = $this->sanitizeTopN($prefs['top_n'] ?? 10);
        $prefs['forecast_horizon'] = $this->sanitizeForecastHorizon($prefs['forecast_horizon'] ?? 3);
        $prefs['forecast_method'] = $this->sanitizeForecastMethod($prefs['forecast_method'] ?? 'moving_avg');

        if (array_key_exists('top_n', $_GET)) {
            $prefs['top_n'] = $this->sanitizeTopN($_GET['top_n']);
        }
        if (array_key_exists('forecast_horizon', $_GET)) {
            $prefs['forecast_horizon'] = $this->sanitizeForecastHorizon($_GET['forecast_horizon']);
        }
        if (array_key_exists('forecast_method', $_GET)) {
            $prefs['forecast_method'] = $this->sanitizeForecastMethod($_GET['forecast_method']);
        }

        $_SESSION['finance_chart_preferences'] = $prefs;

        return $prefs;
    }

    private function sanitizeTopN(mixed $value): int
    {
        $allowed = [5, 10, 15];
        $parsed = (int) $value;
        return in_array($parsed, $allowed, true) ? $parsed : 10;
    }

    private function sanitizeForecastHorizon(mixed $value): int
    {
        $allowed = [0, 3, 6];
        $parsed = (int) $value;
        return in_array($parsed, $allowed, true) ? $parsed : 3;
    }

    private function sanitizeForecastMethod(mixed $value): string
    {
        $parsed = (string) $value;
        return in_array($parsed, ['moving_avg', 'linear_trend'], true) ? $parsed : 'moving_avg';
    }

    private function financeFilterDefaults(): array
    {
        return [
            'search'          => '',
            'bootcamp_type'   => '',
            'related_project' => '',
            'start_date'      => '',
            'end_date'        => '',
            'business_model'  => '',
            'cohort_status'   => '',
        ];
    }

    /**
     * Export the master view with current filters to CSV.
     */
    public function exportMasterCsv(): void
    {
        Auth::requireAccess('cohorts_master');
        $filters = [
            'search'          => (string) $this->input('search', ''),
            'bootcamp_type'   => (string) $this->input('bootcamp_type', ''),
            'related_project' => (string) $this->input('related_project', ''),
            'start_date'      => (string) $this->input('start_date', ''),
            'end_date'        => (string) $this->input('end_date', ''),
            'business_model'  => (string) $this->input('business_model', ''),
            'cohort_status'   => (string) $this->input('cohort_status', ''),
        ];
        $cohorts = $this->cohortService->getFilteredCohorts($filters);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="plan_maestro_cohort_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            http_response_code(500);
            exit;
        }

        // UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'Codigo', 'Cohorte', 'Bootcamp name', 'Proyecto', 'Coach', 'Dias', 'Horario',
            'Meta Total', 'Meta B2B', 'Meta B2C', 'Actual B2B', 'Actual B2C', 'Actual Total',
            'Revenue Meta', 'Revenue Actual', 'Estado', 'Inicio', 'Fin'
        ]);

        foreach ($cohorts as $cohort) {
            $b2b = (int) ($cohort['b2b_admissions'] ?? 0);
            $b2c = (int) ($cohort['b2c_admissions'] ?? 0);
            $statusKey = $this->cohortService->normalizeTrainingStatus((string) ($cohort['training_status'] ?? 'not_started'));
            fputcsv($output, [
                (string) ($cohort['cohort_code'] ?? ''),
                (string) ($cohort['name'] ?? ''),
                (string) ($cohort['bootcamp_type'] ?? ''),
                (string) ($cohort['related_project'] ?? ''),
                (string) ($cohort['assigned_coach'] ?? ''),
                (string) ($cohort['class_days'] ?? ''),
                (string) ($cohort['class_time'] ?? ''),
                (int) ($cohort['total_admission_target'] ?? 0),
                (int) ($cohort['b2b_admission_target'] ?? 0),
                (int) ($cohort['b2c_admission_target'] ?? 0),
                $b2b,
                $b2c,
                $b2b + $b2c,
                (float) ($cohort['financial_target_revenue'] ?? 0),
                (float) ($cohort['financial_actual_revenue'] ?? 0),
                CohortService::STATUS_LABELS[$statusKey] ?? $statusKey,
                (string) ($cohort['start_date'] ?? ''),
                (string) ($cohort['end_date'] ?? ''),
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export the master view with current filters to XLSX.
     */
    public function exportMasterXlsx(): void
    {
        Auth::requireAccess('cohorts_master');
        $filters = [
            'search'          => (string) $this->input('search', ''),
            'bootcamp_type'   => (string) $this->input('bootcamp_type', ''),
            'related_project' => (string) $this->input('related_project', ''),
            'start_date'      => (string) $this->input('start_date', ''),
            'end_date'        => (string) $this->input('end_date', ''),
            'business_model'  => (string) $this->input('business_model', ''),
            'cohort_status'   => (string) $this->input('cohort_status', ''),
        ];
        $cohorts = $this->cohortService->getFilteredCohorts($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plan Maestro');

        $headers = [
            'Codigo', 'Cohorte', 'Bootcamp name', 'Proyecto', 'Coach', 'Dias', 'Horario',
            'Meta Total', 'Meta B2B', 'Meta B2C', 'Actual B2B', 'Actual B2C', 'Actual Total',
            'Revenue Meta', 'Revenue Actual', 'Estado', 'Inicio', 'Fin'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D6EFD'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $row = 2;
        foreach ($cohorts as $cohort) {
            $b2b = (int) ($cohort['b2b_admissions'] ?? 0);
            $b2c = (int) ($cohort['b2c_admissions'] ?? 0);
            $statusKey = $this->cohortService->normalizeTrainingStatus((string) ($cohort['training_status'] ?? 'not_started'));

            $sheet->setCellValue('A' . $row, (string) ($cohort['cohort_code'] ?? ''));
            $sheet->setCellValue('B' . $row, (string) ($cohort['name'] ?? ''));
            $sheet->setCellValue('C' . $row, (string) ($cohort['bootcamp_type'] ?? ''));
            $sheet->setCellValue('D' . $row, (string) ($cohort['related_project'] ?? ''));
            $sheet->setCellValue('E' . $row, (string) ($cohort['assigned_coach'] ?? ''));
            $sheet->setCellValue('F' . $row, (string) ($cohort['class_days'] ?? ''));
            $sheet->setCellValue('G' . $row, (string) ($cohort['class_time'] ?? ''));
            $sheet->setCellValue('H' . $row, (int) ($cohort['total_admission_target'] ?? 0));
            $sheet->setCellValue('I' . $row, (int) ($cohort['b2b_admission_target'] ?? 0));
            $sheet->setCellValue('J' . $row, (int) ($cohort['b2c_admission_target'] ?? 0));
            $sheet->setCellValue('K' . $row, $b2b);
            $sheet->setCellValue('L' . $row, $b2c);
            $sheet->setCellValue('M' . $row, $b2b + $b2c);
            $sheet->setCellValue('N' . $row, (float) ($cohort['financial_target_revenue'] ?? 0));
            $sheet->setCellValue('O' . $row, (float) ($cohort['financial_actual_revenue'] ?? 0));
            $sheet->setCellValue('P' . $row, CohortService::STATUS_LABELS[$statusKey] ?? $statusKey);
            $sheet->setCellValue('Q' . $row, (string) ($cohort['start_date'] ?? ''));
            $sheet->setCellValue('R' . $row, (string) ($cohort['end_date'] ?? ''));

            $row++;
        }

        foreach (range('A', 'R') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'plan_maestro_cohort_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
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

        try {
            $data = $this->collectFormData();
            $this->cohortService->createCohort($data);
            Auth::flash('success', 'Cohorte creada con estado Planificado.');
            $this->redirect('/cohorts');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
            $this->redirect('/cohorts/create');
        }
    }

    /**
     * Display a single cohort.
     */
    public function show(string $id): void
    {
        Auth::requireAccess('cohorts');
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
            'canManageStatus' => Auth::canManageCohortStatus(),
            'editableFields'  => Auth::getEditableCohortFields(),
            'isAdmin'         => Auth::isAdmin(),
            'workflowTransitions' => $this->cohortService->getAllowedStatusTransitions($cohort),
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
            'scripts'         => [
                '/assets/js/cohorts-edit.js',
            ],
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

        $cohort = $this->cohortService->getCohortById((int) $id);
        if (!$cohort) {
            http_response_code(404);
            echo json_encode(['error' => 'Cohorte no encontrada.']);
            return;
        }

        if (Auth::isAdmissionsB2B() && (int) ($cohort['b2b_admission_target'] ?? 0) <= 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Esta cohorte no pertenece al canal B2B para edición de admisiones.']);
            return;
        }

        $b2cTarget = (int) ($cohort['b2c_admission_target'] ?? 0);
        if (Auth::isAdmissionsB2C() && $b2cTarget <= 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Esta cohorte no pertenece al canal B2C para edición de admisiones.']);
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

        try {
            $this->cohortService->updateCohortPartial((int) $id, $filteredData);
            Auth::flash('success', 'Cohorte actualizada.');
            $this->redirect('/cohorts/' . $id);
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
            $this->redirect('/cohorts/' . $id . '/edit');
        }
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

        // Get cohort to check status before deletion
        $cohort = $this->cohortService->getCohortById((int) $id);
        if (!$cohort) {
            http_response_code(404);
            echo json_encode(['error' => 'Cohorte no encontrada.']);
            return;
        }

        // Check if cohort is in progress or completed - block deletion
        $trainingStatus = $cohort['training_status'] ?? 'not_started';
        if (in_array($trainingStatus, ['in_progress', 'completed', 'En progreso', 'Completado'], true)) {
            Auth::flash('error', 'No se pueden eliminar cohortes En progreso o Completadas.');
            $this->redirect('/cohorts/' . $id);
            return;
        }

        try {
            $this->cohortService->deleteCohort((int) $id);
            Auth::flash('success', 'Cohorte eliminada.');
            $this->redirect('/cohorts');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
            $this->redirect('/cohorts/' . $id);
        }
    }

    public function transitionStatus(string $id): void
    {
        if (!Auth::canManageCohortStatus()) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permiso para cambiar el estado de cohortes.']);
            return;
        }

        $cohort = $this->cohortService->getCohortById((int) $id);
        if (!$cohort) {
            Auth::flash('error', 'Cohorte no encontrada.');
            $this->redirect('/cohorts');
        }

        $targetStatus = (string) $this->input('target_status', '');
        $reason = $this->normalizeTextInput($this->input('status_reason'));

        try {
            $this->cohortService->transitionCohortStatus((int) $id, $targetStatus, $reason);
            $normalizedStatus = $this->cohortService->normalizeTrainingStatus($targetStatus);
            $label = CohortService::STATUS_LABELS[$normalizedStatus] ?? $normalizedStatus;
            Auth::flash('success', 'Estado de cohorte actualizado a ' . $label . '.');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
        }

        $this->redirect('/cohorts/' . $id);
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
            'b2c_admission_target'     => (int) $this->input('b2c_admission_target', '0'),
            'b2b_admissions'           => (int) $this->input('b2b_admissions', '0'),
            'b2c_admissions'           => (int) $this->input('b2c_admissions', '0'),
            'financial_target_revenue' => $this->normalizeDecimalInput($this->input('financial_target_revenue', '0')),
            'financial_actual_revenue' => $this->normalizeDecimalInput($this->input('financial_actual_revenue', '0')),
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

    private function normalizeDecimalInput(mixed $value): string
    {
        return trim(str_replace(',', '.', (string) $value));
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
