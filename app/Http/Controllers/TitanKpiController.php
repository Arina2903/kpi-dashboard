<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\SupabaseService;

class TitanKpiController extends Controller
{
    const SHEET_ID = '1BjDBkjIXwvoDTfe2bjAtgqWRbIIjYDyOp-i6hSju-IQ';

    const MONTHS = [
        1  => 'January',   2  => 'February',  3  => 'March',
        4  => 'April',     5  => 'May',        6  => 'June',
        7  => 'July',      8  => 'August',     9  => 'September',
        10 => 'October',   11 => 'November',   12 => 'December',
    ];

    const KPIS = [
        'revenue'   => ['no' => '1.1', 'title' => 'Total Collection Achievement (Revenue)', 'desc' => 'To achieve targeted individual monthly revenue collection.',  'unit' => 'RM'],
        'retention' => ['no' => '1.2', 'title' => 'Client Retention',                       'desc' => 'To retain client.',                                           'unit' => 'clients'],
    ];

    private function currentUser(SupabaseService $supabase): array
    {
        $uuid = session('employee_uuid');
        if (!$uuid) abort(403, 'Not logged in.');
        $rows = $supabase->get('employees', ['id' => 'eq.' . $uuid, 'is_active' => 'eq.true', 'select' => '*']);
        if (empty($rows)) { session()->flush(); abort(403); }
        return $rows[0];
    }

    private function currentFY(): string { return 'FY' . now()->year; }

    private function isTitanUser(array $user): bool
    {
        return $user['role'] !== 'VP' && (
            ($user['company_code'] === 'RCG'  && $user['department_code'] === 'TITAN') ||
            ($user['company_code'] === 'RGHB' && $user['department_code'] === 'BTS')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX — Titan KPI dashboard (auto-sync from Google Sheet on every load)
    |--------------------------------------------------------------------------
    */

    public function index(SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        if (!$this->isTitanUser($user)) abort(403, 'Access restricted to RCG Titan staff.');

        $fy = $this->currentFY();

        // All active Titan staff (non-VP), sorted A–Z
        $allStaff = array_values(array_filter(
            $supabase->get('employees', [
                'company_code'    => 'eq.RCG',
                'department_code' => 'eq.TITAN',
                'is_active'       => 'eq.true',
                'select'          => 'id,employee_id,short_name,full_name,role',
            ]) ?? [],
            fn($e) => $e['role'] !== 'VP'
        ));
        usort($allStaff, fn($a, $b) => strcasecmp($a['short_name'] ?? '', $b['short_name'] ?? ''));

        $isManager = in_array($user['role'], ['MANAGER', 'SLT'])
            || ($user['company_code'] === 'RGHB' && $user['department_code'] === 'BTS');
        $viewStaff = $isManager ? $allStaff
            : array_values(array_filter($allStaff, fn($e) => $e['id'] === $user['id']));

        // Auto-sync: fetch from Google Sheet and upsert actual + base_target into DB
        $sheetData = $this->fetchSheetData();
        $now = now()->toDateTimeString();
        foreach ($allStaff as $emp) {
            $camKey  = strtolower(trim($emp['short_name'] ?? ''));
            $camData = $sheetData[$camKey] ?? [];
            foreach (self::MONTHS as $monthNum => $monthName) {
                foreach (array_keys(self::KPIS) as $kpiKey) {
                    $actual = $camData[$monthNum][$kpiKey]['actual']      ?? 0;
                    $base   = $camData[$monthNum][$kpiKey]['base_target'] ?? 0;
                    $supabase->upsert('titan_monthly_kpi', [
                        'employee_id'    => $emp['id'],
                        'company_code'   => 'RCG',
                        'financial_year' => $fy,
                        'kpi_key'        => $kpiKey,
                        'month_number'   => $monthNum,
                        'month_name'     => $monthName,
                        'actual'         => $actual,
                        'base_target'    => $base,
                        'weightage'      => 10,
                        'synced_at'      => $now,
                        'updated_at'     => $now,
                    ], 'employee_id,financial_year,kpi_key,month_number');
                }
            }
        }

        // Read back from DB (preserves any manual weightage edits)
        $monthlyData = [];
        foreach ($viewStaff as $emp) {
            $rows = $supabase->get('titan_monthly_kpi', [
                'employee_id'    => 'eq.' . $emp['id'],
                'financial_year' => 'eq.' . $fy,
                'select'         => 'kpi_key,month_number,actual,base_target,weightage',
            ]) ?? [];
            foreach ($rows as $r) {
                $monthlyData[$emp['id']][$r['kpi_key']][$r['month_number']] = $r;
            }
        }

        return view('titan.kpi', compact('user', 'fy', 'allStaff', 'viewStaff', 'monthlyData', 'isManager'));
    }

    /*
    |--------------------------------------------------------------------------
    | FETCH SHEET DATA — parse Google Sheet CSV into per-CAM KPI values
    |    revenue:   actual = Total Collected (sum of monthly payments)
    |               base   = Potential Collection (sum of client Amount)
    |    retention: actual = clients who paid that month
    |               base   = total clients with a membership amount
    |--------------------------------------------------------------------------
    */

    private function fetchSheetData(): array
    {
        $csvUrl   = 'https://docs.google.com/spreadsheets/d/' . self::SHEET_ID . '/export?format=csv';
        $response = Http::timeout(30)->get($csvUrl);
        if (!$response->successful()) return [];

        $lines = explode("\n", str_replace("\r", '', trim($response->body())));
        $rows  = array_map('str_getcsv', $lines);

        // Locate header row (contains both "CAM" and "January")
        $headerRow = null;
        $headerIdx = 0;
        foreach ($rows as $i => $row) {
            if (in_array('CAM', $row) && in_array('January', $row)) {
                $headerRow = $row;
                $headerIdx = $i;
                break;
            }
        }
        if (!$headerRow) return [];

        $camCol    = array_search('CAM',    $headerRow);
        $amountCol = array_search('Amount', $headerRow);

        // Month column indices — first occurrence of each month name
        $monthCols = [];
        foreach (self::MONTHS as $num => $name) {
            foreach ($headerRow as $col => $val) {
                if ($val === $name && !isset($monthCols[$num])) {
                    $monthCols[$num] = $col;
                }
            }
        }

        // Group client rows by CAM name (lowercase)
        $dataRows = array_slice($rows, $headerIdx + 1);
        $byCam    = [];
        foreach ($dataRows as $row) {
            $cam = strtolower(trim(str_replace("\n", '', $row[$camCol] ?? '')));
            if ($cam !== '') $byCam[$cam][] = $row;
        }

        // Compute per-CAM, per-month Potential Collection (base) and Total Collected (actual)
        $result = [];
        foreach ($byCam as $camKey => $clientRows) {
            // Potential Collection = sum of membership Amount for all clients with an Amount
            $clientsWithAmt = array_filter($clientRows, fn($r) => trim($r[$amountCol] ?? '') !== '');
            $baseRevenue    = 0.0;
            foreach ($clientsWithAmt as $r) {
                $baseRevenue += (float) preg_replace('/[^0-9.]/', '', $r[$amountCol]);
            }
            $baseRetention = count($clientsWithAmt);

            foreach ($monthCols as $monthNum => $monthCol) {
                $actualRevenue   = 0.0;
                $actualRetention = 0;

                foreach ($clientRows as $r) {
                    $monthVal = trim($r[$monthCol] ?? '');
                    if ($monthVal === '') continue;

                    $monthAmount = (float) preg_replace('/[^0-9.]/', '', $monthVal);
                    // Numeric amount → use directly; non-numeric mark (tick/✓) → use client's Amount
                    $actualRevenue += $monthAmount > 0
                        ? $monthAmount
                        : (float) preg_replace('/[^0-9.]/', '', $r[$amountCol] ?? '0');
                    $actualRetention++;
                }

                $result[$camKey][$monthNum]['revenue']   = ['actual' => $actualRevenue,   'base_target' => $baseRevenue];
                $result[$camKey][$monthNum]['retention'] = ['actual' => $actualRetention, 'base_target' => $baseRetention];
            }
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC — pull data from Google Sheet and upsert
    |--------------------------------------------------------------------------
    */

    public function sync(Request $request, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        if (!$this->isTitanUser($user) || !in_array($user['role'], ['MANAGER', 'SLT'])) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $fy = $this->currentFY();

        // All Titan staff (non-VP)
        $employees = array_values(array_filter(
            $supabase->get('employees', [
                'company_code'    => 'eq.RCG',
                'department_code' => 'eq.TITAN',
                'is_active'       => 'eq.true',
                'select'          => 'id,short_name',
            ]) ?? [],
            fn($e) => $e['role'] !== 'VP'
        ));

        // Fetch CSV from Google Sheet (first/only sheet)
        $csvUrl  = 'https://docs.google.com/spreadsheets/d/' . self::SHEET_ID . '/export?format=csv';
        $response = Http::timeout(30)->get($csvUrl);
        if (!$response->successful()) {
            return response()->json(['error' => 'Cannot reach Google Sheet.'], 502);
        }

        $lines = explode("\n", trim($response->body()));
        $rows  = array_map('str_getcsv', $lines);

        // Find header row containing "CAM" and "January"
        $headerRow = null;
        $headerIdx = 0;
        foreach ($rows as $i => $row) {
            if (in_array('CAM', $row) && in_array('January', $row)) {
                $headerRow = $row;
                $headerIdx = $i;
                break;
            }
        }

        if (!$headerRow) {
            return response()->json(['error' => 'Header row not found in sheet.'], 500);
        }

        $camCol    = array_search('CAM',    $headerRow);
        $amountCol = array_search('Amount', $headerRow);

        // Month column indexes — take FIRST occurrence of each month name
        $monthCols = [];
        foreach (self::MONTHS as $num => $name) {
            foreach ($headerRow as $col => $val) {
                if ($val === $name && !isset($monthCols[$num])) {
                    $monthCols[$num] = $col;
                }
            }
        }

        // Group data rows by CAM name (lowercase)
        $dataRows = array_slice($rows, $headerIdx + 1);
        $byCam    = [];
        foreach ($dataRows as $row) {
            $cam = strtolower(trim($row[$camCol] ?? ''));
            if ($cam !== '') $byCam[$cam][] = $row;
        }

        $now    = now()->toDateTimeString();
        $synced = 0;

        foreach ($employees as $emp) {
            $camKey     = strtolower(trim($emp['short_name'] ?? ''));
            $clientRows = $byCam[$camKey] ?? [];

            // Clients with a non-empty Amount
            $clientsWithAmt = array_filter($clientRows, fn($r) => trim($r[$amountCol] ?? '') !== '');

            $baseRetention = count($clientsWithAmt);
            $baseRevenue   = 0;
            foreach ($clientsWithAmt as $r) {
                $baseRevenue += (float) preg_replace('/[^0-9.]/', '', $r[$amountCol]);
            }

            foreach ($monthCols as $monthNum => $monthCol) {
                $actualRevenue   = 0.0;
                $actualRetention = 0;

                foreach ($clientRows as $r) {
                    $monthVal = trim($r[$monthCol] ?? '');
                    if ($monthVal === '') continue;

                    $monthAmount = (float) preg_replace('/[^0-9.]/', '', $monthVal);
                    if ($monthAmount > 0) {
                        $actualRevenue += $monthAmount;
                    } else {
                        // Non-numeric mark (e.g. tick) — use contract amount
                        $actualRevenue += (float) preg_replace('/[^0-9.]/', '', $r[$amountCol] ?? '0');
                    }
                    $actualRetention++;
                }

                foreach (['revenue' => [$actualRevenue, $baseRevenue], 'retention' => [$actualRetention, $baseRetention]] as $key => [$actual, $base]) {
                    $supabase->upsert('titan_monthly_kpi', [
                        'employee_id'    => $emp['id'],
                        'company_code'   => 'RCG',
                        'financial_year' => $fy,
                        'kpi_key'        => $key,
                        'month_number'   => $monthNum,
                        'month_name'     => self::MONTHS[$monthNum],
                        'actual'         => $actual,
                        'base_target'    => $base,
                        'weightage'      => 10,
                        'synced_at'      => $now,
                        'updated_at'     => $now,
                    ], 'employee_id,financial_year,kpi_key,month_number');
                    $synced++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Synced data for ' . count($employees) . ' staff across ' . count($monthCols) . ' months.',
            'synced'  => $synced,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE WEIGHTAGE — manager can adjust per month/KPI
    |--------------------------------------------------------------------------
    */

    public function updateWeightage(Request $request, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        if (!$this->isTitanUser($user) || !in_array($user['role'], ['MANAGER', 'SLT'])) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $request->validate([
            'employee_id'  => 'required|uuid',
            'kpi_key'      => 'required|in:revenue,retention',
            'month_number' => 'required|integer|min:1|max:12',
            'weightage'    => 'required|numeric|min:0|max:100',
        ]);

        $fy = $this->currentFY();
        $supabase->patch('titan_monthly_kpi', [
            'employee_id'    => 'eq.' . $request->employee_id,
            'financial_year' => 'eq.' . $fy,
            'kpi_key'        => 'eq.' . $request->kpi_key,
            'month_number'   => 'eq.' . $request->month_number,
        ], ['weightage' => $request->weightage, 'updated_at' => now()->toDateTimeString()]);

        return response()->json(['success' => true]);
    }
}
