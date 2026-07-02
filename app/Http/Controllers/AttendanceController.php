<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\SupabaseService;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    const WORK_START = '08:30';

    private function authorise(): bool
    {
        return session('hr_access') === true;
    }

    public function index(SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid') || !session()->has('company_code')) {
            return redirect()->route('login');
        }
        if (!$this->authorise()) abort(403, 'Access restricted to SLT and VP only.');

        $company = strtoupper(session('company_code'));
        $year    = now()->year;

        $existingData = $supabase->get('attendance_summary', [
            'company_code' => 'eq.' . $company,
            'year'         => 'eq.' . $year,
            'select'       => 'month,updated_at',
        ]) ?? [];

        $monthStatus = [];
        foreach ($existingData as $row) {
            $m = (int) $row['month'];
            if (!isset($monthStatus[$m]) || $row['updated_at'] > $monthStatus[$m]) {
                $monthStatus[$m] = $row['updated_at'];
            }
        }

        return view('attendance.index', [
            'defaultMonth'   => now()->month,
            'defaultYear'    => $year,
            'monthStatus'    => $monthStatus,
            'statusYear'     => $year,
            'defaultCompany' => $company,
        ]);
    }

    public function import(Request $request, SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid')) return redirect()->route('login');
        if (!$this->authorise()) abort(403, 'Access restricted to SLT and VP only.');

        $request->validate([
            'sheet_url' => 'required|url',
            'month'     => 'required|integer|min:1|max:12',
            'year'      => 'required|integer|min:2024|max:2030',
            'company'   => 'required|string',
        ]);

        $month    = (int) $request->month;
        $year     = (int) $request->year;
        $company  = strtoupper($request->company);
        $sheetUrl = $request->sheet_url;

        preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9_-]+)/', $sheetUrl, $m);
        $sheetId = $m[1] ?? null;
        if (!$sheetId) return back()->with('error', 'Invalid Google Sheet URL.');

        $allPh = $supabase->get('public_holidays', ['select' => 'holiday_date']) ?? [];
        $publicHolidays = array_values(array_filter(
            array_column($allPh, 'holiday_date'),
            fn($d) => Carbon::parse($d)->year === $year && Carbon::parse($d)->month === $month
        ));

        $start       = Carbon::create($year, $month, 1);
        $end         = $start->copy()->endOfMonth();
        $workingDays = [];
        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $ds = $d->toDateString();
            if (!in_array($d->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) && !in_array($ds, $publicHolidays)) {
                $workingDays[] = $ds;
            }
        }
        $totalWorkingDays = count($workingDays);

        $monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        $tabName    = $monthNames[$month - 1];
        $csvUrl     = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&sheet={$tabName}";
        $response   = Http::timeout(30)->get($csvUrl);
        if (!$response->successful()) {
            $csvUrl   = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv";
            $response = Http::timeout(30)->get($csvUrl);
        }
        if (!$response->successful()) {
            return back()->with('error', 'Could not fetch the Google Sheet. Make sure it is set to "Anyone with link can view".');
        }

        $dbEmps = $supabase->get('employees', [
            'company_code' => 'eq.' . $company,
            'is_active'    => 'eq.true',
            'select'       => 'id,employee_id,full_name,short_name,email,department_code',
        ]) ?? [];

        $results = $this->parseAttendanceCsv($response->body(), $month, $year, $workingDays, $dbEmps);

        return view('attendance.index', [
            'results'          => $results,
            'workingDays'      => $workingDays,
            'totalWorkingDays' => $totalWorkingDays,
            'publicHolidays'   => $publicHolidays,
            'month'            => $month,
            'year'             => $year,
            'company'          => $company,
            'sheetUrl'         => $sheetUrl,
            'defaultMonth'     => $month,
            'defaultYear'      => $year,
            'defaultCompany'   => $company,
            'monthStatus'      => [],
            'statusYear'       => $year,
        ]);
    }

    // Returns JSON preview data — does NOT auto-save
    public function importAll(Request $request, SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid')) return response()->json(['error' => 'Unauthenticated'], 401);
        if (!$this->authorise()) return response()->json(['error' => 'Access restricted.'], 403);

        $request->validate([
            'sheet_url' => 'required|url',
            'company'   => 'required|string',
            'year'      => 'nullable|integer',
        ]);

        $company  = strtoupper($request->company);
        $year     = (int) ($request->year ?? now()->year);
        $sheetUrl = $request->sheet_url;

        preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9_-]+)/', $sheetUrl, $m);
        $sheetId = $m[1] ?? null;
        if (!$sheetId) return response()->json(['error' => 'Invalid Google Sheet URL.'], 422);

        $allPh = $supabase->get('public_holidays', ['select' => 'holiday_date']) ?? [];
        $phByMonth = [];
        foreach ($allPh as $ph) {
            $d = Carbon::parse($ph['holiday_date']);
            if ($d->year === $year) $phByMonth[$d->month][] = $ph['holiday_date'];
        }

        // Pull all employees (no company filter — import everyone in the sheet)
        $dbEmps = $supabase->get('employees', [
            'is_active' => 'eq.true',
            'select'    => 'id,employee_id,full_name,short_name,email,department_code,company_code',
        ]) ?? [];

        $monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        $upToMonth  = ($year < now()->year) ? 12 : now()->month;
        $monthsData = [];

        for ($month = 1; $month <= $upToMonth; $month++) {
            $monthName = $monthNames[$month - 1];

            $start = Carbon::create($year, $month, 1);
            $end   = $start->copy()->endOfMonth();
            $ph    = $phByMonth[$month] ?? [];
            $workingDays = [];
            for ($d = $start->copy(); $d <= $end; $d->addDay()) {
                $ds = $d->toDateString();
                if (!in_array($d->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) && !in_array($ds, $ph)) {
                    $workingDays[] = $ds;
                }
            }

            $csvUrl   = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&sheet={$monthName}";
            $response = Http::timeout(30)->get($csvUrl);

            if (!$response->successful()) {
                $monthsData[$month] = ['success' => false, 'month_name' => $monthName, 'error' => "Tab '{$monthName}' not found."];
                continue;
            }

            $results = $this->parseAttendanceCsv($response->body(), $month, $year, $workingDays, $dbEmps);

            if (empty($results)) {
                $monthsData[$month] = ['success' => false, 'month_name' => $monthName, 'error' => "No employees found in {$monthName}."];
                continue;
            }

            // Strip daily records — only summary fields needed for preview
            $employees = [];
            foreach ($results as $eid => $emp) {
                $employees[] = [
                    'internal_id'        => $emp['internal_id'],
                    'db_employee_id'     => $emp['db_employee_id'] ?? null,
                    'name'               => $emp['name'],
                    'preferred_name'     => $emp['preferred_name'],
                    'department'         => $emp['department'],
                    'working_days'       => $emp['working_days'],
                    'present_days'       => $emp['present_days'],
                    'absent_days'        => $emp['absent_days'],
                    'late_count'         => $emp['late_count'],
                    'total_late_minutes' => $emp['total_late_minutes'],
                ];
            }

            $monthsData[$month] = [
                'success'      => true,
                'month_name'   => $monthName,
                'working_days' => count($workingDays),
                'employees'    => $employees,
            ];
        }

        return response()->json(['success' => true, 'year' => $year, 'company' => $company, 'months' => $monthsData]);
    }

    // Save bulk preview data (all months)
    public function saveAll(Request $request, SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid')) return response()->json(['error' => 'Unauthenticated'], 401);
        if (!$this->authorise()) return response()->json(['error' => 'Access restricted.'], 403);

        $data = $request->validate([
            'year'    => 'required|integer',
            'company' => 'required|string',
            'months'  => 'required|array',
        ]);

        $year    = (int) $data['year'];
        $company = strtoupper($data['company']);
        $now     = now()->toISOString();
        $saved   = 0;

        foreach ($data['months'] as $monthNum => $mData) {
            foreach ($mData['employees'] as $rec) {
                $mc    = (int) ($rec['mc_days']          ?? 0);
                $al    = (int) ($rec['al_days']          ?? 0);
                $other = (int) ($rec['other_leave_days'] ?? 0);
                $payload = [
                    'internal_id'        => $rec['internal_id'],
                    'employee_id'        => $rec['db_employee_id'] ?: null,
                    'company_code'       => $company,
                    'month'              => (int) $monthNum,
                    'year'               => $year,
                    'working_days'       => (int) $rec['working_days'],
                    'present_days'       => (int) $rec['present_days'],
                    'absent_days'        => max(0, (int) $rec['absent_days'] - $mc - $al - $other),
                    'late_count'         => (int) $rec['late_count'],
                    'total_late_minutes' => (int) $rec['total_late_minutes'],
                    'mc_days'            => $mc,
                    'al_days'            => $al,
                    'other_leave_days'   => $other,
                    'finalized'          => true,
                    'updated_at'         => $now,
                ];
                $supabase->upsert('attendance_summary', $payload, 'internal_id,month,year');
                $saved++;
            }
        }

        return response()->json(['success' => true, 'saved' => $saved]);
    }

    public function save(Request $request, SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid')) return response()->json(['error' => 'Unauthenticated'], 401);
        if (!$this->authorise()) return response()->json(['error' => 'Access restricted.'], 403);

        $data = $request->validate([
            'records'   => 'required|array',
            'month'     => 'required|integer',
            'year'      => 'required|integer',
            'company'   => 'required|string',
            'sheet_url' => 'nullable|string',
        ]);

        foreach ($data['records'] as $rec) {
            $payload = [
                'internal_id'        => $rec['internal_id'],
                'employee_id'        => $rec['db_employee_id'] ?: null,
                'company_code'       => $data['company'],
                'month'              => $data['month'],
                'year'               => $data['year'],
                'working_days'       => (int) $rec['working_days'],
                'present_days'       => (int) $rec['present_days'],
                'absent_days'        => (int) ($rec['absent_days'] - ($rec['mc_days'] + $rec['al_days'] + $rec['other_leave_days'])),
                'late_count'         => (int) $rec['late_count'],
                'total_late_minutes' => (int) $rec['total_late_minutes'],
                'mc_days'            => (int) $rec['mc_days'],
                'al_days'            => (int) $rec['al_days'],
                'other_leave_days'   => (int) $rec['other_leave_days'],
                'sheet_url'          => $data['sheet_url'] ?? null,
                'finalized'          => true,
                'updated_at'         => now()->toISOString(),
            ];
            $supabase->upsert('attendance_summary', $payload, 'internal_id,month,year');
        }

        return response()->json(['success' => true, 'count' => count($data['records'])]);
    }

    // Parses CSV → employee summary. No company-prefix filter so all employees in sheet are included.
    private function parseAttendanceCsv(string $csvText, int $month, int $year, array $workingDays, array $dbEmps): array
    {
        $cutoff  = self::WORK_START;
        $totalWD = count($workingDays);
        $lines   = array_filter(explode("\n", str_replace("\r", '', $csvText)));
        $rows    = array_map('str_getcsv', $lines);
        array_shift($rows);

        $employees = [];
        foreach ($rows as $row) {
            if (count($row) < 7) continue;
            $internalId  = trim($row[0] ?? '');
            $firstName   = trim($row[1] ?? '');
            $lastName    = trim($row[2] ?? '');
            $preferred   = trim($row[3] ?? '');
            $email       = trim($row[4] ?? '');
            $clockIn     = trim($row[5] ?? '');
            $clockInDate = trim($row[6] ?? '');

            if (empty($internalId) || empty($clockInDate) || empty($clockIn)) continue;

            try {
                $dt = Carbon::parse($clockInDate);
                if ($dt->month !== $month || $dt->year !== $year) continue;
            } catch (\Exception $e) { continue; }

            if (!isset($employees[$internalId])) {
                $employees[$internalId] = [
                    'internal_id'    => $internalId,
                    'name'           => trim("{$firstName} {$lastName}"),
                    'preferred_name' => $preferred ?: $firstName,
                    'email'          => $email,
                    'dates'          => [],
                ];
            }
            if (!isset($employees[$internalId]['dates'][$clockInDate])) {
                $employees[$internalId]['dates'][$clockInDate] = $clockIn;
            } elseif ($clockIn < $employees[$internalId]['dates'][$clockInDate]) {
                $employees[$internalId]['dates'][$clockInDate] = $clockIn;
            }
        }

        // Merge DB employees (enrich with department; add if missing from CSV)
        foreach ($dbEmps as $emp) {
            $eid = $emp['employee_id'] ?? null;
            if (!$eid) continue;
            if (!isset($employees[$eid])) {
                $employees[$eid] = [
                    'internal_id'    => $eid,
                    'name'           => $emp['full_name'] ?? $emp['short_name'] ?? '—',
                    'preferred_name' => $emp['short_name'] ?? '',
                    'email'          => $emp['email'] ?? '',
                    'dates'          => [],
                    'db_employee_id' => $emp['id'],
                    'department'     => $emp['department_code'] ?? '',
                ];
            } else {
                $employees[$eid]['db_employee_id'] = $emp['id'];
                $employees[$eid]['department']     = $emp['department_code'] ?? '';
            }
        }

        $results = [];
        foreach ($employees as $eid => $emp) {
            $presentDays = $lateCount = $totalLateMinutes = 0;
            $dailyRecords = [];

            foreach ($workingDays as $wd) {
                if (isset($emp['dates'][$wd])) {
                    $ci     = $emp['dates'][$wd];
                    $presentDays++;
                    $ciNorm = strlen($ci) === 4 ? "0{$ci}" : $ci;
                    $isLate = $ciNorm > $cutoff;
                    $lateMins = 0;
                    if ($isLate) {
                        $lateCount++;
                        $lateMins = (int) Carbon::parse($wd . ' ' . $ciNorm)
                                        ->diffInMinutes(Carbon::parse($wd . ' ' . $cutoff));
                        $totalLateMinutes += $lateMins;
                    }
                    $dailyRecords[$wd] = ['status' => 'present', 'clock_in' => $ciNorm, 'is_late' => $isLate, 'late_minutes' => $lateMins];
                } else {
                    $dailyRecords[$wd] = ['status' => 'absent', 'clock_in' => null, 'is_late' => false, 'late_minutes' => 0];
                }
            }

            $results[$eid] = [
                'internal_id'        => $eid,
                'db_employee_id'     => $emp['db_employee_id'] ?? null,
                'name'               => $emp['name'],
                'preferred_name'     => $emp['preferred_name'],
                'email'              => $emp['email'],
                'department'         => $emp['department'] ?? '',
                'working_days'       => $totalWD,
                'present_days'       => $presentDays,
                'absent_days'        => $totalWD - $presentDays,
                'late_count'         => $lateCount,
                'total_late_minutes' => $totalLateMinutes,
                'mc_days'            => 0,
                'al_days'            => 0,
                'other_leave_days'   => 0,
                'daily'              => $dailyRecords,
            ];
        }

        uasort($results, fn($a, $b) => strcmp($a['department'] . $a['name'], $b['department'] . $b['name']));
        return $results;
    }
}
