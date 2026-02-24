<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\ReportService;

/**
 * ReportController
 *
 * HTTP layer for the Reports module.
 * Handles filtered views, Excel export, and PDF generation.
 */
class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct()
    {
        Auth::requireLogin();
        $this->reportService = new ReportService();
    }

    /**
     * Display the main reports dashboard with filters, metrics, and table.
     * GET /reports
     */
    public function index(): void
    {
        $filters    = [];
        $error      = null;
        $reportData = null;

        // Apply filters if present
        try {
            $filters    = $this->reportService->validateFilters($this->allInput());
            $reportData = $this->reportService->getReportData($filters);
        } catch (\InvalidArgumentException $e) {
            $error      = $e->getMessage();
            $reportData = $this->reportService->getReportData([]);
        }

        $this->view('reports.index', [
            'pageTitle'    => 'Reportes',
            'activePage'   => 'reports',
            'filters'      => $filters,
            'rawFilters'   => $this->allInput(),
            'reportData'   => $reportData,
            'areaLabels'   => ReportService::AREA_LABELS,
            'statusLabels' => ReportService::STATUS_LABELS,
            'error'        => $error,
        ]);
    }

    /**
     * Export filtered cohorts to Excel.
     * GET /reports/export/excel
     */
    public function exportExcel(): void
    {
        try {
            $filters    = $this->reportService->validateFilters($this->allInput());
            $reportData = $this->reportService->getReportData($filters);

            $this->reportService->exportToExcel($reportData['cohorts'], $filters);
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
            $this->redirect('/reports');
        } catch (\RuntimeException $e) {
            Auth::flash('error', $e->getMessage());
            $this->redirect('/reports');
        }
    }

    /**
     * Export/preview filtered report as PDF.
     * GET /reports/export/pdf?mode=download|preview
     */
    public function exportPdf(): void
    {
        try {
            $filters    = $this->reportService->validateFilters($this->allInput());
            $reportData = $this->reportService->getReportData($filters);
            $mode       = $this->input('mode', 'download');

            if (!in_array($mode, ['download', 'preview'], true)) {
                $mode = 'download';
            }

            $this->reportService->exportToPdf($reportData, $filters, $mode);
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
            $this->redirect('/reports');
        } catch (\RuntimeException $e) {
            Auth::flash('error', $e->getMessage());
            $this->redirect('/reports');
        }
    }
}
