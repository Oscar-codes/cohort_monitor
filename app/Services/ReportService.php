<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * ReportService
 *
 * Business logic for the Reports module.
 * Provides filtering, metric calculation, Excel export, and PDF generation.
 */
class ReportService
{
    private ReportRepository $reportRepo;

    /** Human-readable labels for training statuses */
    public const STATUS_LABELS = [
        'completed'   => 'Completado',
        'in_progress' => 'En progreso',
        'not_started' => 'No iniciado',
        'cancelled'   => 'Cancelado',
    ];

    public function __construct()
    {
        $this->reportRepo = new ReportRepository();
    }

    // â”€â”€â”€ Filtering â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Validate and sanitize report filters.
     *
     * @param array $raw  Raw input from the request
     * @return array       Sanitized filters
     * @throws \InvalidArgumentException
     */
    public function validateFilters(array $raw): array
    {
        $filters = [];
        $areaLabels = $this->getAreaLabels();

        // Area filter
        $area = trim($raw['area'] ?? '');
        if ($area !== '' && $area !== 'all') {
            if (!array_key_exists($area, $areaLabels)) {
                throw new \InvalidArgumentException('Ãrea no vÃ¡lida.');
            }
            $filters['area'] = $area;
        }

        // Date range
        $dateFrom = trim($raw['date_from'] ?? '');
        $dateTo   = trim($raw['date_to'] ?? '');

        if ($dateFrom !== '') {
            if (!$this->isValidDate($dateFrom)) {
                throw new \InvalidArgumentException('Fecha "Desde" no tiene formato vÃ¡lido (YYYY-MM-DD).');
            }
            $filters['date_from'] = $dateFrom;
        }

        if ($dateTo !== '') {
            if (!$this->isValidDate($dateTo)) {
                throw new \InvalidArgumentException('Fecha "Hasta" no tiene formato vÃ¡lido (YYYY-MM-DD).');
            }
            $filters['date_to'] = $dateTo;
        }

        // Cross-validate date range
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            if ($filters['date_from'] > $filters['date_to']) {
                throw new \InvalidArgumentException('La fecha "Desde" no puede ser mayor que "Hasta".');
            }
        }

        return $filters;
    }

    /**
     * Get the full report data: cohorts, area metrics, and status metrics.
     *
     * @param array $filters Validated filters
     * @return array ['cohorts' => [...], 'byArea' => [...], 'byStatus' => [...]]
     */
    public function getReportData(array $filters = []): array
    {
        $cohorts    = $this->reportRepo->getFilteredCohorts($filters);
        $byArea     = $this->reportRepo->getMetricsByArea($filters);
        $byStatus   = $this->reportRepo->getMetricsByStatus($filters);
        $areaLabels = $this->getAreaLabels();

        // Ensure all areas are represented
        foreach (array_keys($areaLabels) as $area) {
            if (!isset($byArea[$area])) {
                $byArea[$area] = [
                    'area'        => $area,
                    'total'       => 0,
                    'at_risk'     => 0,
                    'completed'   => 0,
                    'in_progress' => 0,
                ];
            }
        }

        return [
            'cohorts'  => $cohorts,
            'byArea'   => $byArea,
            'byStatus' => $byStatus,
        ];
    }

    /**
     * Get distinct area labels from database routes.
     *
     * @return array<string, string>
     */
    public function getAreaLabels(): array
    {
        $rows = $this->reportRepo->getDistinctAreas();
        $labels = [];

        foreach ($rows as $row) {
            $key = strtolower((string) ($row['area'] ?? ''));
            if ($key === '') {
                continue;
            }

            $labels[$key] = ucwords($key);
        }

        return $labels;
    }

    // â”€â”€â”€ Excel Export â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Generate an Excel (.xlsx) file from filtered cohort data and force download.
     *
     * @param array $cohorts  Filtered cohort rows
     * @param array $filters  Applied filters (for sheet metadata)
     * @throws \RuntimeException if no data to export
     */
    public function exportToExcel(array $cohorts, array $filters = []): void
    {
            $areaLabels = $this->getAreaLabels();

        if (empty($cohorts)) {
            throw new \RuntimeException('No hay datos para exportar.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte de Cohortes');

        // â”€â”€ Title row â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'Reporte de Cohortes â€” Cohort Monitor');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // â”€â”€ Filter info â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $filterText = 'Filtros: ';
        $filterText .= !empty($filters['area']) ? 'Ãrea: ' . ($areaLabels[$filters['area']] ?? $filters['area']) : 'Todas las Ã¡reas';
        $filterText .= !empty($filters['date_from']) ? ' | Desde: ' . $filters['date_from'] : '';
        $filterText .= !empty($filters['date_to'])   ? ' | Hasta: ' . $filters['date_to']   : '';
        $filterText .= ' | Generado: ' . date('d/m/Y H:i');

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', $filterText);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

        // â”€â”€ Column headers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $headers = ['Nombre del Cohort', 'Ãrea', 'Estado', 'Fecha Inicio', 'Fecha Fin', 'En Riesgo'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        // Style headers
        $headerRange = 'A4:F4';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D6EFD'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // â”€â”€ Data rows â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $row = 5;
        foreach ($cohorts as $c) {
            $sheet->setCellValue('A' . $row, $c['name']);
            $sheet->setCellValue('B' . $row, $areaLabels[$c['area'] ?? ''] ?? ($c['area'] ?? 'â€”'));
            $sheet->setCellValue('C' . $row, self::STATUS_LABELS[$c['training_status'] ?? ''] ?? ($c['training_status'] ?? 'â€”'));
            $sheet->setCellValue('D' . $row, $c['start_date'] ?? 'â€”');
            $sheet->setCellValue('E' . $row, $c['end_date']   ?? 'â€”');
            $sheet->setCellValue('F' . $row, ($c['at_risk'] ?? 0) ? 'SÃ­' : 'No');

            // Highlight at-risk rows
            if (!empty($c['at_risk'])) {
                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF3CD'],
                    ],
                ]);
            }

            $row++;
        }

        // â”€â”€ Auto-size columns â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        foreach (range('A', 'F') as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // â”€â”€ Borders â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $lastRow = $row - 1;
        $sheet->getStyle("A4:F{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // â”€â”€ Download â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $filename = 'reporte_cohortes_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // â”€â”€â”€ PDF Export â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Generate a PDF report and either download or render inline for printing.
     *
     * @param array  $reportData  Full report data (cohorts, byArea, byStatus)
     * @param array  $filters     Applied filters
     * @param string $mode        'download' or 'preview'
     */
    public function exportToPdf(array $reportData, array $filters = [], string $mode = 'download'): void
    {
        if (empty($reportData['cohorts'])) {
            throw new \RuntimeException('No hay datos para generar el PDF.');
        }

        // Build HTML content
        $html = $this->buildPdfHtml($reportData, $filters);

        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'reporte_cohortes_' . date('Y-m-d_His') . '.pdf';

        if ($mode === 'preview') {
            // Inline display for browser print
            $dompdf->stream($filename, ['Attachment' => false]);
        } else {
            // Force download
            $dompdf->stream($filename, ['Attachment' => true]);
        }

        exit;
    }

    /**
     * Build the HTML for the PDF report.
     */
    private function buildPdfHtml(array $reportData, array $filters): string
    {
        $cohorts  = $reportData['cohorts'];
        $byArea   = $reportData['byArea'];
        $byStatus = $reportData['byStatus'];
        $areaLabels = $this->getAreaLabels();

        $filterDesc = [];
        if (!empty($filters['area'])) {
            $filterDesc[] = 'Ãrea: ' . ($areaLabels[$filters['area']] ?? $filters['area']);
        } else {
            $filterDesc[] = 'Ãrea: Todas';
        }
        if (!empty($filters['date_from'])) {
            $filterDesc[] = 'Desde: ' . $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $filterDesc[] = 'Hasta: ' . $filters['date_to'];
        }
        $filterStr = implode(' | ', $filterDesc);

        // â”€â”€ Area summary rows â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $areaSummaryHtml = '';
        foreach ($areaLabels as $key => $label) {
            $a = $byArea[$key] ?? ['total' => 0, 'at_risk' => 0, 'completed' => 0, 'in_progress' => 0];
            $areaSummaryHtml .= "<tr>
                <td><strong>{$label}</strong></td>
                <td style='text-align:center'>{$a['total']}</td>
                <td style='text-align:center'>{$a['at_risk']}</td>
                <td style='text-align:center'>{$a['completed']}</td>
                <td style='text-align:center'>{$a['in_progress']}</td>
            </tr>";
        }

        // â”€â”€ Cohort rows â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $cohortRowsHtml = '';
        foreach ($cohorts as $c) {
            $areaLabel  = $areaLabels[$c['area'] ?? ''] ?? ($c['area'] ?? 'â€”');
            $statusLabel = self::STATUS_LABELS[$c['training_status'] ?? ''] ?? ($c['training_status'] ?? 'â€”');
            $riskLabel  = ($c['at_risk'] ?? 0) ? '<span style="color:#dc3545;font-weight:bold">SÃ­</span>' : 'No';
            $riskBg     = ($c['at_risk'] ?? 0) ? 'background-color:#fff3cd;' : '';

            $cohortRowsHtml .= "<tr style='{$riskBg}'>
                <td>{$c['name']}</td>
                <td style='text-align:center'>{$areaLabel}</td>
                <td style='text-align:center'>{$statusLabel}</td>
                <td style='text-align:center'>{$c['start_date']}</td>
                <td style='text-align:center'>{$c['end_date']}</td>
                <td style='text-align:center'>{$riskLabel}</td>
            </tr>";
        }

        $now = date('d/m/Y H:i');
        $totalCohorts = count($cohorts);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cohortes â€” Cohort Monitor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #333; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
        .header h1 { font-size: 18px; color: #0d6efd; margin-bottom: 4px; }
        .header p { font-size: 9px; color: #666; }
        .filters { background: #f8f9fa; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 9px; }
        .section-title { font-size: 12px; font-weight: bold; color: #0d6efd; margin: 15px 0 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background-color: #0d6efd; color: white; font-weight: bold; padding: 6px 8px; text-align: center; font-size: 9px; }
        td { padding: 5px 8px; border-bottom: 1px solid #dee2e6; font-size: 9px; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .summary-grid { display: flex; gap: 10px; margin-bottom: 15px; }
        .metric-box { background: #f0f4ff; border: 1px solid #0d6efd; border-radius: 4px; padding: 8px; text-align: center; flex: 1; }
        .metric-box .value { font-size: 18px; font-weight: bold; color: #0d6efd; }
        .metric-box .label { font-size: 8px; color: #666; }
        .footer { text-align: center; font-size: 8px; color: #999; margin-top: 20px; border-top: 1px solid #dee2e6; padding-top: 8px; }
        .status-grid { margin-bottom: 15px; }
        .status-grid table { width: auto; margin: 0 auto; }
        .status-grid td, .status-grid th { text-align: center; padding: 4px 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Cohortes</h1>
        <p>Cohort Monitor â€” Generado: {$now}</p>
    </div>

    <div class="filters">
        <strong>Filtros aplicados:</strong> {$filterStr} &nbsp;|&nbsp; <strong>Total de cohortes:</strong> {$totalCohorts}
    </div>

    <div class="section-title">Resumen por Ãrea</div>
    <table>
        <thead>
            <tr>
                <th>Ãrea</th>
                <th>Total</th>
                <th>En Riesgo</th>
                <th>Completadas</th>
                <th>En EjecuciÃ³n</th>
            </tr>
        </thead>
        <tbody>
            {$areaSummaryHtml}
        </tbody>
    </table>

    <div class="section-title">Resumen por Estado</div>
    <div class="status-grid">
        <table>
            <thead>
                <tr>
                    <th>Completado</th>
                    <th>En EjecuciÃ³n</th>
                    <th>No iniciado</th>
                    <th>Cancelado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{$byStatus['completed']}</strong></td>
                    <td><strong>{$byStatus['in_progress']}</strong></td>
                    <td><strong>{$byStatus['not_started']}</strong></td>
                    <td><strong>{$byStatus['cancelled']}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section-title">Detalle de Cohortes</div>
    <table>
        <thead>
            <tr>
                <th style="text-align:left">Nombre del Cohort</th>
                <th>Ãrea</th>
                <th>Estado</th>
                <th>Fecha Inicio</th>
                <th>Fecha Fin</th>
                <th>En Riesgo</th>
            </tr>
        </thead>
        <tbody>
            {$cohortRowsHtml}
        </tbody>
    </table>

    <div class="footer">
        Cohort Monitor v1.2 â€” Este reporte fue generado automÃ¡ticamente. Los datos reflejan el estado al momento de la generaciÃ³n.
    </div>
</body>
</html>
HTML;
    }

    // â”€â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Validate that a string is a valid date in Y-m-d format.
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}


