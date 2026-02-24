<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\CohortImportService;

/**
 * ImportCohortController
 *
 * Handles the bulk cohort import feature.
 * Only accessible by Administrators.
 */
class ImportCohortController extends Controller
{
    private CohortImportService $importService;

    public function __construct()
    {
        Auth::requireLogin();

        // Only admins can import cohorts
        if (!Auth::isAdmin()) {
            Auth::flash('error', 'No tienes permisos para importar cohortes.');
            $this->redirect('/cohorts');
        }

        $this->importService = new CohortImportService();
    }

    /**
     * Show the import form.
     * GET /cohorts/import
     */
    public function showForm(): void
    {
        $this->view('cohorts.import', [
            'pageTitle'  => 'Importar Cohortes',
            'activePage' => 'import',
            'breadcrumb' => [
                ['label' => 'Cohortes', 'url' => '/cohorts'],
                ['label' => 'Importar', 'active' => true],
            ],
        ]);
    }

    /**
     * Handle the file upload and import.
     * POST /cohorts/import
     */
    public function handleImport(): void
    {
        $file = $_FILES['import_file'] ?? [];

        // Validate file
        $validation = $this->importService->validateFile($file);

        if (!$validation['valid']) {
            $this->view('cohorts.import', [
                'pageTitle'  => 'Importar Cohortes',
                'activePage' => 'import',
                'breadcrumb' => [
                    ['label' => 'Cohortes', 'url' => '/cohorts'],
                    ['label' => 'Importar', 'active' => true],
                ],
                'error' => $validation['error'],
            ]);
            return;
        }

        // Process file
        $result  = $this->importService->processFile($file['tmp_name'], $file['name']);
        $summary = $this->importService->generateImportSummary($result);

        $this->view('cohorts.import', [
            'pageTitle'  => 'Importar Cohortes — Resultados',
            'activePage' => 'import',
            'breadcrumb' => [
                ['label' => 'Cohortes', 'url' => '/cohorts'],
                ['label' => 'Importar', 'active' => true],
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * Download the Excel template.
     * GET /cohorts/import/template
     */
    public function downloadTemplate(): void
    {
        $this->importService->downloadTemplate();
    }
}
