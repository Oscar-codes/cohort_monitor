<?php

namespace App\Services;

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * CohortImportService
 *
 * Business logic for bulk-importing cohorts from Excel/CSV files.
 * Validates each row, detects duplicates, and performs optimized inserts.
 */
class CohortImportService
{
    private Database $db;

    /** Maximum file size in bytes (5 MB) */
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /** Allowed MIME types */
    public const ALLOWED_MIMES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel',                                          // .xls
        'text/csv',                                                          // .csv
        'text/plain',                                                        // some CSVs
        'application/csv',
        'application/octet-stream',                                          // edge cases
    ];

    /** Allowed file extensions */
    public const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    /** Expected column headers (lowercase, trimmed) mapped to DB fields */
    public const COLUMN_MAP = [
        'name'           => 'name',
        'nombre'         => 'name',
        'area'           => 'area',
        'área'           => 'area',
        'type'           => 'bootcamp_type',
        'tipo'           => 'bootcamp_type',
        'project'        => 'related_project',
        'proyecto'       => 'related_project',
        'start_date'     => 'start_date',
        'fecha_inicio'   => 'start_date',
        'end_date'       => 'end_date',
        'fecha_fin'      => 'end_date',
        'meta_total'     => 'total_admission_target',
        'meta_b2b'       => 'b2b_admission_target',
        'admissions_b2c' => 'b2c_admissions',
        'b2c'            => 'b2c_admissions',
        'status'         => 'training_status',
        'estado'         => 'training_status',
        'at_risk'        => 'at_risk',
        'en_riesgo'      => 'at_risk',
    ];

    /** Required DB fields (must be present after mapping) */
    public const REQUIRED_FIELDS = ['name', 'start_date', 'end_date'];

    /** Valid area values */
    public const VALID_AREAS = ['academic', 'marketing', 'admissions'];

    /** Status text mappings (flexible input → DB value) */
    public const STATUS_MAP = [
        'completado'   => 'completed',
        'completed'    => 'completed',
        'en ejecución' => 'in_progress',
        'en ejecucion' => 'in_progress',
        'en progreso'  => 'in_progress',
        'in_progress'  => 'in_progress',
        'in progress'  => 'in_progress',
        'en proceso'   => 'not_started',
        'pendiente'    => 'not_started',
        'not_started'  => 'not_started',
        'not started'  => 'not_started',
        'cancelado'    => 'cancelled',
        'cancelled'    => 'cancelled',
    ];

    /** Area text mappings (flexible input → DB value) */
    public const AREA_MAP = [
        'academic'    => 'academic',
        'académico'   => 'academic',
        'academico'   => 'academic',
        'marketing'   => 'marketing',
        'admissions'  => 'admissions',
        'admisiones'  => 'admissions',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── File Validation ─────────────────────────────────────

    /**
     * Validate the uploaded file (type, size, extension).
     *
     * @param array $file  $_FILES['import_file'] entry
     * @return array        ['valid' => bool, 'error' => ?string]
     */
    public function validateFile(array $file): array
    {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            $msgs = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño permitido por el servidor.',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño permitido por el formulario.',
                UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente.',
                UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Error temporal del servidor.',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco.',
            ];
            return ['valid' => false, 'error' => $msgs[$code] ?? 'Error al subir el archivo.'];
        }

        // Size check
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $maxMb = self::MAX_FILE_SIZE / (1024 * 1024);
            return ['valid' => false, 'error' => "El archivo excede el tamaño máximo permitido ({$maxMb} MB)."];
        }

        // Extension check
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return ['valid' => false, 'error' => 'Formato no permitido. Solo se aceptan archivos .xlsx, .xls o .csv.'];
        }

        return ['valid' => true, 'error' => null];
    }

    // ─── File Processing ─────────────────────────────────────

    /**
     * Process the uploaded file: read, validate rows, and insert valid ones.
     *
     * @param string $filePath  Temp path of the uploaded file
     * @param string $originalName  Original filename (to detect format)
     * @return array  Import summary
     */
    public function processFile(string $filePath, string $originalName): array
    {
        $summary = [
            'total_rows'  => 0,
            'inserted'    => 0,
            'failed'      => 0,
            'errors'      => [],  // ['row' => int, 'field' => string, 'message' => string]
            'skipped_duplicates' => 0,
        ];

        // Read spreadsheet
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet   = $spreadsheet->getActiveSheet();
            $rows        = $worksheet->toArray(null, true, true, true);
        } catch (\Throwable $e) {
            $summary['errors'][] = [
                'row'     => 0,
                'field'   => 'archivo',
                'message' => 'No se pudo leer el archivo: ' . $e->getMessage(),
            ];
            return $summary;
        }

        if (count($rows) < 2) {
            $summary['errors'][] = [
                'row'     => 0,
                'field'   => 'archivo',
                'message' => 'El archivo está vacío o no contiene filas de datos.',
            ];
            return $summary;
        }

        // Parse header row (first row)
        $headerRow = array_shift($rows);
        $columnMapping = $this->mapHeaders($headerRow);

        if (empty($columnMapping)) {
            $summary['errors'][] = [
                'row'     => 1,
                'field'   => 'encabezados',
                'message' => 'No se reconocieron las columnas del archivo. Verifica los encabezados.',
            ];
            return $summary;
        }

        // Check required columns exist
        $mappedFields = array_values($columnMapping);
        foreach (self::REQUIRED_FIELDS as $req) {
            if (!in_array($req, $mappedFields, true)) {
                $summary['errors'][] = [
                    'row'     => 1,
                    'field'   => $req,
                    'message' => "Falta la columna obligatoria: {$req}",
                ];
            }
        }

        if (!empty($summary['errors'])) {
            return $summary;
        }

        // Load existing cohorts for duplicate detection (name + start_date)
        $existingKeys = $this->getExistingCohortKeys();

        // Process each data row
        $validRows = [];
        $rowNumber = 1; // header was row 1

        foreach ($rows as $row) {
            $rowNumber++;
            $rawData = $this->extractRowData($row, $columnMapping);

            // Skip completely empty rows
            if ($this->isEmptyRow($rawData)) {
                continue;
            }

            $summary['total_rows']++;

            // Validate row
            $rowErrors = $this->validateRow($rawData, $rowNumber);

            if (!empty($rowErrors)) {
                $summary['failed']++;
                foreach ($rowErrors as $err) {
                    $summary['errors'][] = $err;
                }
                continue;
            }

            // Normalize values
            $normalized = $this->normalizeRow($rawData);

            // Duplicate check
            $dupeKey = strtolower(trim($normalized['name'])) . '|' . ($normalized['start_date'] ?? '');
            if (isset($existingKeys[$dupeKey])) {
                $summary['skipped_duplicates']++;
                $summary['failed']++;
                $summary['errors'][] = [
                    'row'     => $rowNumber,
                    'field'   => 'name + start_date',
                    'message' => "Duplicado: ya existe una cohorte \"{$normalized['name']}\" con fecha de inicio {$normalized['start_date']}.",
                ];
                continue;
            }

            // Mark as existing to prevent in-file duplicates
            $existingKeys[$dupeKey] = true;
            $normalized['_row'] = $rowNumber;
            $validRows[] = $normalized;
        }

        // Bulk insert valid rows
        if (!empty($validRows)) {
            $insertResult = $this->bulkInsert($validRows, $summary);
            $summary = $insertResult;
        }

        return $summary;
    }

    // ─── Header Mapping ──────────────────────────────────────

    /**
     * Map spreadsheet column letters to DB field names.
     *
     * @param array $headerRow  Associative [column_letter => header_value]
     * @return array             [column_letter => db_field_name]
     */
    private function mapHeaders(array $headerRow): array
    {
        $mapping = [];

        foreach ($headerRow as $col => $header) {
            if ($header === null) continue;

            $key = strtolower(trim((string) $header));
            // Remove BOM and special chars
            $key = preg_replace('/[\x{FEFF}]/u', '', $key);
            $key = trim($key);

            if (isset(self::COLUMN_MAP[$key])) {
                $mapping[$col] = self::COLUMN_MAP[$key];
            }
        }

        return $mapping;
    }

    /**
     * Extract a row's data using the column mapping.
     */
    private function extractRowData(array $row, array $columnMapping): array
    {
        $data = [];
        foreach ($columnMapping as $col => $field) {
            $value = $row[$col] ?? null;
            $data[$field] = $value !== null ? trim((string) $value) : null;
        }
        return $data;
    }

    /**
     * Check if a row is completely empty.
     */
    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }
        return true;
    }

    // ─── Row Validation ──────────────────────────────────────

    /**
     * Validate a single data row.
     *
     * @return array  List of error entries (empty = valid)
     */
    private function validateRow(array $data, int $rowNumber): array
    {
        $errors = [];

        // Required: name
        if (empty($data['name'])) {
            $errors[] = ['row' => $rowNumber, 'field' => 'name', 'message' => 'El nombre es obligatorio.'];
        }

        // Validate dates
        foreach (['start_date', 'end_date'] as $dateField) {
            $val = $data[$dateField] ?? '';
            if ($val !== '') {
                $parsed = $this->parseDate($val);
                if ($parsed === null) {
                    $errors[] = [
                        'row'     => $rowNumber,
                        'field'   => $dateField,
                        'message' => "Fecha inválida: \"{$val}\". Use formato YYYY-MM-DD o DD/MM/YYYY.",
                    ];
                }
            }
        }

        // end_date >= start_date
        $startParsed = $this->parseDate($data['start_date'] ?? '');
        $endParsed   = $this->parseDate($data['end_date'] ?? '');
        if ($startParsed && $endParsed && $endParsed < $startParsed) {
            $errors[] = [
                'row'     => $rowNumber,
                'field'   => 'end_date',
                'message' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            ];
        }

        // Validate area
        $area = strtolower(trim($data['area'] ?? ''));
        if ($area !== '' && !isset(self::AREA_MAP[$area])) {
            $errors[] = [
                'row'     => $rowNumber,
                'field'   => 'area',
                'message' => "Área no válida: \"{$data['area']}\". Valores permitidos: Academic, Marketing, Admissions.",
            ];
        }

        // Validate status
        $status = strtolower(trim($data['training_status'] ?? ''));
        if ($status !== '' && !isset(self::STATUS_MAP[$status])) {
            $errors[] = [
                'row'     => $rowNumber,
                'field'   => 'status',
                'message' => "Estado no válido: \"{$data['training_status']}\". Valores permitidos: Completado, En ejecución, Pendiente, Cancelado.",
            ];
        }

        // Validate numeric fields
        foreach (['total_admission_target', 'b2b_admission_target', 'b2c_admissions'] as $numField) {
            $val = $data[$numField] ?? '';
            if ($val !== '' && !is_numeric($val)) {
                $errors[] = [
                    'row'     => $rowNumber,
                    'field'   => $numField,
                    'message' => "El campo {$numField} debe ser numérico. Valor recibido: \"{$val}\".",
                ];
            }
        }

        // Validate at_risk
        $risk = strtolower(trim($data['at_risk'] ?? ''));
        if ($risk !== '' && !in_array($risk, ['sí', 'si', 'no', 'yes', '1', '0', 'true', 'false'], true)) {
            $errors[] = [
                'row'     => $rowNumber,
                'field'   => 'at_risk',
                'message' => "Valor de riesgo no válido: \"{$data['at_risk']}\". Usar Sí o No.",
            ];
        }

        return $errors;
    }

    // ─── Normalization ───────────────────────────────────────

    /**
     * Normalize a validated row to DB-ready values.
     */
    private function normalizeRow(array $data): array
    {
        // Name
        $normalized = ['name' => trim($data['name'])];

        // Area
        $area = strtolower(trim($data['area'] ?? ''));
        $normalized['area'] = self::AREA_MAP[$area] ?? null;

        // Bootcamp type
        $normalized['bootcamp_type'] = !empty($data['bootcamp_type']) ? trim($data['bootcamp_type']) : null;

        // Project
        $normalized['related_project'] = !empty($data['related_project']) ? trim($data['related_project']) : null;

        // Dates
        $normalized['start_date'] = $this->parseDate($data['start_date'] ?? '');
        $normalized['end_date']   = $this->parseDate($data['end_date'] ?? '');

        // Numeric fields
        $normalized['total_admission_target'] = $this->toInt($data['total_admission_target'] ?? '');
        $normalized['b2b_admission_target']   = $this->toInt($data['b2b_admission_target'] ?? '');
        $normalized['b2c_admissions']         = $this->toInt($data['b2c_admissions'] ?? '');

        // Status
        $status = strtolower(trim($data['training_status'] ?? ''));
        $normalized['training_status'] = self::STATUS_MAP[$status] ?? 'not_started';

        // At risk
        $risk = strtolower(trim($data['at_risk'] ?? ''));
        $normalized['at_risk'] = in_array($risk, ['sí', 'si', 'yes', '1', 'true'], true) ? 1 : 0;

        // Generate cohort_code from name
        $normalized['cohort_code'] = $this->generateCohortCode($normalized['name']);

        return $normalized;
    }

    /**
     * Parse a date string into YYYY-MM-DD format.
     * Supports: YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, MM/DD/YYYY, Excel serial.
     */
    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;

        // Excel serial number
        if (is_numeric($value) && (int)$value > 30000 && (int)$value < 60000) {
            try {
                $dateTime = ExcelDate::excelToDateTimeObject((float)$value);
                return $dateTime->format('Y-m-d');
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // Standard formats
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'];
        foreach ($formats as $fmt) {
            $dt = \DateTimeImmutable::createFromFormat($fmt, $value);
            if ($dt !== false && $dt->format($fmt) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        // Last resort: strtotime
        $ts = strtotime($value);
        if ($ts !== false && $ts > 0) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    private function toInt(string $val): int
    {
        $val = trim($val);
        return $val !== '' && is_numeric($val) ? (int) $val : 0;
    }

    /**
     * Generate a cohort code from the name (uppercase abbreviation + timestamp suffix).
     */
    private function generateCohortCode(string $name): string
    {
        $words = preg_split('/[\s\-_]+/', $name);
        $abbr  = '';
        foreach ($words as $w) {
            if ($w !== '') {
                $abbr .= strtoupper(mb_substr($w, 0, 1));
            }
        }
        $abbr = substr($abbr, 0, 5);
        $suffix = strtoupper(substr(uniqid(), -4));
        return $abbr . '-' . $suffix;
    }

    // ─── Duplicate Detection ─────────────────────────────────

    /**
     * Build a set of existing cohort composite keys: "lowercase_name|start_date".
     */
    private function getExistingCohortKeys(): array
    {
        $rows = $this->db->query('SELECT name, start_date FROM cohorts');
        $keys = [];
        foreach ($rows as $r) {
            $key = strtolower(trim($r['name'])) . '|' . ($r['start_date'] ?? '');
            $keys[$key] = true;
        }
        return $keys;
    }

    // ─── Bulk Insert ─────────────────────────────────────────

    /**
     * Insert rows individually inside a transaction.
     * Continues on per-row failures, collects errors.
     */
    private function bulkInsert(array $validRows, array $summary): array
    {
        $this->db->beginTransaction();

        try {
            foreach ($validRows as $row) {
                $rowNum = $row['_row'];
                unset($row['_row']);

                try {
                    $this->db->execute(
                        'INSERT INTO cohorts (
                            cohort_code, name,
                            total_admission_target, b2b_admission_target, b2c_admissions,
                            start_date, end_date,
                            related_project, bootcamp_type, area,
                            training_status, at_risk,
                            created_at, updated_at
                        ) VALUES (
                            :cohort_code, :name,
                            :total_admission_target, :b2b_admission_target, :b2c_admissions,
                            :start_date, :end_date,
                            :related_project, :bootcamp_type, :area,
                            :training_status, :at_risk,
                            NOW(), NOW()
                        )',
                        [
                            'cohort_code'            => $row['cohort_code'],
                            'name'                   => $row['name'],
                            'total_admission_target' => $row['total_admission_target'],
                            'b2b_admission_target'   => $row['b2b_admission_target'],
                            'b2c_admissions'         => $row['b2c_admissions'],
                            'start_date'             => $row['start_date'],
                            'end_date'               => $row['end_date'],
                            'related_project'        => $row['related_project'],
                            'bootcamp_type'          => $row['bootcamp_type'],
                            'area'                   => $row['area'],
                            'training_status'        => $row['training_status'],
                            'at_risk'                => $row['at_risk'],
                        ]
                    );
                    $summary['inserted']++;
                } catch (\Throwable $e) {
                    $summary['failed']++;
                    $summary['errors'][] = [
                        'row'     => $rowNum,
                        'field'   => 'db',
                        'message' => 'Error al insertar: ' . $e->getMessage(),
                    ];
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $summary['errors'][] = [
                'row'     => 0,
                'field'   => 'transacción',
                'message' => 'Error en la transacción: ' . $e->getMessage(),
            ];
        }

        return $summary;
    }

    // ─── Template Generation ─────────────────────────────────

    /**
     * Generate and download a sample Excel template.
     */
    public function downloadTemplate(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla Cohorts');

        // Headers
        $headers = [
            'A' => 'name',
            'B' => 'area',
            'C' => 'type',
            'D' => 'project',
            'E' => 'start_date',
            'F' => 'end_date',
            'G' => 'meta_total',
            'H' => 'meta_b2b',
            'I' => 'admissions_b2c',
            'J' => 'status',
            'K' => 'at_risk',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getStyle("{$col}1")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('0D6EFD');
            $sheet->getStyle("{$col}1")->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Example row
        $sheet->setCellValue('A2', 'Full Stack Cohort 01');
        $sheet->setCellValue('B2', 'Academic');
        $sheet->setCellValue('C2', 'Full Stack');
        $sheet->setCellValue('D2', 'Proyecto Alpha');
        $sheet->setCellValue('E2', '2026-03-01');
        $sheet->setCellValue('F2', '2026-09-01');
        $sheet->setCellValue('G2', 30);
        $sheet->setCellValue('H2', 10);
        $sheet->setCellValue('I2', 20);
        $sheet->setCellValue('J2', 'Pendiente');
        $sheet->setCellValue('K2', 'No');

        // Second example
        $sheet->setCellValue('A3', 'UX/UI Cohort 02');
        $sheet->setCellValue('B3', 'Marketing');
        $sheet->setCellValue('C3', 'UX/UI');
        $sheet->setCellValue('D3', 'Proyecto Beta');
        $sheet->setCellValue('E3', '2026-04-15');
        $sheet->setCellValue('F3', '2026-10-15');
        $sheet->setCellValue('G3', 25);
        $sheet->setCellValue('H3', 5);
        $sheet->setCellValue('I3', 20);
        $sheet->setCellValue('J3', 'En ejecución');
        $sheet->setCellValue('K3', 'Sí');

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="plantilla_cohorts.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ─── Summary Helpers ─────────────────────────────────────

    /**
     * Build a human-readable import summary.
     */
    public function generateImportSummary(array $result): array
    {
        return [
            'total_processed'    => $result['total_rows'],
            'inserted_ok'        => $result['inserted'],
            'failed'             => $result['failed'],
            'duplicates_skipped' => $result['skipped_duplicates'],
            'errors'             => $result['errors'],
            'has_errors'         => !empty($result['errors']),
            'success'            => $result['inserted'] > 0,
        ];
    }
}
