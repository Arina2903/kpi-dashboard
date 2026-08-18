<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Performix Platform Blueprint, Phase 7: Upload -> Read -> Validate. Preview
 * and Confirm/Import live in ImportController — this service only ever
 * parses a file into plain associative records and checks them for
 * problems; it never touches the database, so the same validated result can
 * be shown to the Center before anything is committed (spec section 14).
 *
 * Column mapping is intentionally lossy in a couple of places, documented
 * here rather than silently dropped:
 *  - KPI "Weight" has nowhere to go — the live `kpis` table has no weight
 *    column at all (a real gap in the Platform's scoring model, out of
 *    scope for an import pipeline to invent). Kept under `_weight` so the
 *    Preview screen can still show what was in the file.
 *  - Employee "Position"/"Manager"/"Join Date" have nowhere to go either —
 *    `department_users` has no columns for any of them. Kept as plain
 *    metadata on the staged row (see ImportController::confirm()) for
 *    whenever Phase 8's account-creation step actually needs them, rather
 *    than invented as new columns nobody asked for yet.
 */
class ExcelImportService
{
    private const DEPARTMENT_HEADERS = [
        'department code' => 'code',
        'code' => 'code',
        'department name' => 'name',
        'name' => 'name',
        'status' => 'status',
    ];

    private const EMPLOYEE_HEADERS = [
        'employee code' => 'employee_code',
        'name' => 'name',
        'email' => 'email',
        'department' => 'department_code',
        'department code' => 'department_code',
        'position' => 'position',
        'manager' => 'manager',
        'join date' => 'join_date',
        'status' => 'status',
    ];

    private const KPI_HEADERS = [
        'kpi code' => 'kpi_code',
        'kpi name' => 'name',
        'name' => 'name',
        'description' => 'description',
        'measurement type' => 'measurement_type',
        'weight' => 'weight',
        'target type' => 'target_type',
        'status' => 'status',
    ];

    /**
     * $type is one of 'departments', 'employees', 'kpis', or 'workbook'
     * (one file with multiple sheets, matched by name). Returns only the
     * keys relevant to $type — a single-type upload never returns the other
     * two, even as empty arrays, so callers can tell "not part of this
     * upload" apart from "sheet was present but empty."
     */
    public function parse(UploadedFile $file, string $type): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $reader = $extension === 'csv' ? new CsvReader() : new XlsxReader();
        $reader->open($file->getRealPath());

        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $rows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            $sheets[$this->normalizeSheetName($sheet->getName())][] = $rows;
        }
        $reader->close();

        // normalizeSheetName groups by target type, so a CSV's single
        // unnamed sheet still needs a home — treat it as the requested type.
        // Only when the file has exactly one sheet, though: falling back to
        // "whichever sheet came first" on a multi-sheet file with no name
        // match (e.g. an "Instructions" cover sheet ahead of the real data)
        // used to silently parse the wrong sheet into mostly-empty garbage
        // records instead of telling the Center the sheet couldn't be found.
        if ($type !== 'workbook') {
            if (isset($sheets[$type])) {
                $rows = $sheets[$type][0];
            } elseif (count($sheets) === 1) {
                $rows = array_values($sheets)[0][0];
            } else {
                throw new \RuntimeException(
                    "Could not find a sheet matching \"{$type}\" in that file — rename the sheet to include \"{$type}\" in its name, or use the combined workbook option instead."
                );
            }

            $headerMap = match ($type) {
                'departments' => self::DEPARTMENT_HEADERS,
                'employees' => self::EMPLOYEE_HEADERS,
                'kpis' => self::KPI_HEADERS,
            };

            return [$type => $this->rowsToRecords($rows, $headerMap)];
        }

        return [
            'departments' => $this->rowsToRecords($sheets['departments'][0] ?? [], self::DEPARTMENT_HEADERS),
            'employees' => $this->rowsToRecords($sheets['employees'][0] ?? [], self::EMPLOYEE_HEADERS),
            'kpis' => $this->rowsToRecords($sheets['kpis'][0] ?? [], self::KPI_HEADERS),
        ];
    }

    private function normalizeSheetName(string $name): string
    {
        $name = strtolower(trim($name));

        return match (true) {
            str_contains($name, 'department') => 'departments',
            str_contains($name, 'employee') => 'employees',
            str_contains($name, 'kpi') => 'kpis',
            default => $name,
        };
    }

    /**
     * First row is the header; every following row becomes a record keyed by
     * the mapped column name, tagged with its spreadsheet row number for
     * error messages.
     *
     * Known limitation: the row number is this row's position among the
     * rows OpenSpout actually returned, not a true XLSX row index — OpenSpout
     * has no public API for the latter (confirmed against its own source;
     * RowIterator::key() carries a TODO acknowledging it returns "number of
     * rows read" rather than the real index). This only drifts from the
     * physical spreadsheet row when a row was skipped so completely that no
     * `<row>` XML element exists for it at all (confirmed via a raw XLSX
     * inspection: OpenSpout's own writer does this for an all-empty row).
     * A row a person actually typed into and then cleared keeps its element
     * and reads back at its true position. Trailing blank rows — the common
     * real-world case — never trigger this, since nothing comes after them
     * for the count to drift against.
     */
    private function rowsToRecords(array $rows, array $headerMap): array
    {
        if (count($rows) < 2) {
            return [];
        }

        $header = array_map(fn ($h) => strtolower(trim((string) ($h ?? ''))), $rows[0]);
        $columnKeys = array_map(fn ($h) => $headerMap[$h] ?? null, $header);

        $records = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $record = ['_row' => $i + 2]; // +2: 1-indexed, plus the header row itself.

            foreach ($columnKeys as $col => $key) {
                if ($key !== null) {
                    $value = $row[$col] ?? null;
                    $record[$key] = $value !== null && $value !== '' ? trim((string) $value) : null;
                }
            }

            $hasAnyValue = count(array_filter(
                $record,
                fn ($v, $k) => $k !== '_row' && $v !== null,
                ARRAY_FILTER_USE_BOTH,
            )) > 0;

            if ($hasAnyValue) {
                $records[] = $record;
            }
        }

        return $records;
    }

    public function validateDepartments(array $records, array $existingCodes): array
    {
        $errors = [];
        $valid = [];
        $seenCodes = [];

        foreach ($records as $r) {
            $row = $r['_row'];

            if (empty($r['name'])) {
                $errors[] = ['row' => $row, 'message' => 'Department name is required.'];
                continue;
            }
            if (empty($r['code'])) {
                $errors[] = ['row' => $row, 'message' => 'Department code is required.'];
                continue;
            }

            $code = strtoupper($r['code']);

            if (in_array($code, $existingCodes, true)) {
                $errors[] = ['row' => $row, 'message' => "Department code \"{$code}\" already exists."];
                continue;
            }
            if (isset($seenCodes[$code])) {
                $errors[] = ['row' => $row, 'message' => "Department code \"{$code}\" is duplicated within this file (also row {$seenCodes[$code]})."];
                continue;
            }

            $seenCodes[$code] = $row;
            $valid[] = ['code' => $code, 'name' => $r['name'], 'status' => $this->normalizeStatus($r['status'] ?? null)];
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /** $availableDepartmentCodes: existing DB codes, plus (for a combined workbook upload) codes from the Departments sheet in the same file. */
    public function validateEmployees(array $records, array $availableDepartmentCodes, array $existingEmails): array
    {
        $errors = [];
        $valid = [];
        $seenEmails = [];

        foreach ($records as $r) {
            $row = $r['_row'];

            if (empty($r['name'])) {
                $errors[] = ['row' => $row, 'message' => 'Employee name is required.'];
                continue;
            }
            if (empty($r['email']) || !filter_var($r['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['row' => $row, 'message' => 'Employee email is invalid.'];
                continue;
            }
            if (empty($r['department_code'])) {
                $errors[] = ['row' => $row, 'message' => 'Department is required.'];
                continue;
            }

            $departmentCode = strtoupper($r['department_code']);

            if (!in_array($departmentCode, $availableDepartmentCodes, true)) {
                $errors[] = ['row' => $row, 'message' => "Department \"{$departmentCode}\" does not exist."];
                continue;
            }

            $emailLower = strtolower($r['email']);

            if (in_array($emailLower, $existingEmails, true)) {
                $errors[] = ['row' => $row, 'message' => "Employee email \"{$r['email']}\" already exists in this company."];
                continue;
            }
            if (isset($seenEmails[$emailLower])) {
                $errors[] = ['row' => $row, 'message' => "Employee email \"{$r['email']}\" is duplicated within this file (also row {$seenEmails[$emailLower]})."];
                continue;
            }

            $seenEmails[$emailLower] = $row;

            $valid[] = [
                'employee_code' => $r['employee_code'] ?? null,
                'name' => $r['name'],
                'email' => $r['email'],
                'department_code' => $departmentCode,
                'position' => $r['position'] ?? null,
                'manager' => $r['manager'] ?? null,
                'join_date' => $r['join_date'] ?? null,
                'status' => $r['status'] ?? 'active',
            ];
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    public function validateKpis(array $records): array
    {
        $errors = [];
        $valid = [];

        foreach ($records as $r) {
            $row = $r['_row'];

            if (empty($r['name'])) {
                $errors[] = ['row' => $row, 'message' => 'KPI name is required.'];
                continue;
            }

            $valid[] = [
                'name' => $r['name'],
                'description' => $r['description'] ?? null,
                'unit' => $r['measurement_type'] ?? null,
                'frequency' => $this->normalizeFrequency($r['target_type'] ?? null),
                'status' => $this->normalizeStatus($r['status'] ?? null),
                '_weight' => $r['weight'] ?? null,
            ];
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /** Matches KpiController::store()'s own validation set exactly — imported and manually-created KPIs must accept the same values. */
    private function normalizeFrequency(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['daily', 'weekly', 'monthly', 'quarterly', 'custom'], true) ? $value : 'monthly';
    }

    private function normalizeStatus(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return $value === 'inactive' ? 'inactive' : 'active';
    }
}
