<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\SupabaseService;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // Working hours: 08:30 MY time
    const WORK_START = '08:30';

    private function authorise(): bool
    {
        return in_array(session('role'), ['SLT', 'VP']);
    }

    public function index()
    {
        if (!session()->has('employee_uuid') || !session()->has('company_code')) {
            return redirect()->route('login');
        }

        if (!$this->authorise()) {
            abort(403, 'Access restricted to SLT and VP only.');
        }

        return view('attendance.index', [
            'defaultMonth' => now()->month,
            'defaultYear'  => now()->year,
        ]);
    }

    public function import(Request $request, SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid')) return redirect()->route('login');

        if (!$this->authorise()) {
            abort(403, 'Access restricted to SLT and VP only.');
        }

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

        // Extract Google Sheet ID
        preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9_-]+)/', $sheetUrl, $m);
        $sheetId = $m[1] ?? null;
        if (!$sheetId) {
            return back()->with('error', 'Invalid Google Sheet URL. Please check and try again.');
        }

        // Fetch CSV export
        $csvUrl  = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv";
        $response = Http::timeout(30)->get($csvUrl);

        if (!$response->successful()) {
            return back()->with('error', 'Could not fetch the Google Sheet. Make sure it is set to "Anyone with link can view".');
        }

        $csvText = $response->body();
        $lines   = array_filter(explode("\n", str_replace("\r", '', $csvText)));
        $rows    = array_map('str_getcsv', $lines);
        array_shift($rows); // remove header

        // ── Public holidays (fetch whole year, filter by month in PHP) ────
        // Cannot use duplicate array keys for gte+lte in one call
        $allPh = $supabase->get('public_holidays', [
            'select' => 'holiday_date',
        ]) ?? [];
        $publicHolidays = array_values(array_filter(
            array_column($allPh, 'holiday_date'),
            fn($d) => \Carbon\Carbon::parse($d)->year === $year
                   && \Carbon\Carbon::parse($d)->month === $month
        ));

        // ── Build all working days in the month ────────────────────────────
        $start       = Carbon::create($year, $month, 1);
        $end         = $start->copy()->endOfMonth();
        $workingDays = [];
        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $ds = $d->toDateString();
            if (!in_array($d->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
                && !in_array($ds, $publicHolidays)) {
                $workingDays[] = $ds;
            }
        }
        $totalWorkingDays = count($workingDays);

        // ── Parse rows → group by employee → group by date ─────────────────
        $employees = []; // internal_id → { meta, dates: { date → earliest_clock_in } }

        foreach ($rows as $row) {
            if (count($row) < 7) continue;

            $internalId = trim($row[0] ?? '');
            $firstName  = trim($row[1] ?? '');
            $lastName   = trim($row[2] ?? '');
            $preferred  = trim($row[3] ?? '');
            $email      = trim($row[4] ?? '');
            $clockIn    = trim($row[5] ?? '');
            $clockInDate= trim($row[6] ?? '');

            if (empty($internalId) || empty($clockInDate) || empty($clockIn)) continue;

            // Only include this company
            if (!str_starts_with($internalId, $company)) continue;

            // Only include dates in this month/year
            try {
                $dt = Carbon::parse($clockInDate);
                if ($dt->month !== $month || $dt->year !== $year) continue;
            } catch (\Exception $e) {
                continue;
            }

            if (!isset($employees[$internalId])) {
                $employees[$internalId] = [
                    'internal_id'   => $internalId,
                    'name'          => trim("{$firstName} {$lastName}"),
                    'preferred_name'=> $preferred ?: $firstName,
                    'email'         => $email,
                    'dates'         => [],
                ];
            }

            // Take earliest clock-in for each date
            if (!isset($employees[$internalId]['dates'][$clockInDate])) {
                $employees[$internalId]['dates'][$clockInDate] = $clockIn;
            } else {
                $existing = $employees[$internalId]['dates'][$clockInDate];
                if ($clockIn < $existing) {
                    $employees[$internalId]['dates'][$clockInDate] = $clockIn;
                }
            }
        }

        // ── Also pull employees from DB who have NO clock-in at all ────────
        $dbEmps = $supabase->get('employees', [
            'company_code' => 'eq.' . $company,
            'is_active'    => 'eq.true',
            'select'       => 'id,employee_id,full_name,short_name,email,department_code',
        ]) ?? [];

        foreach ($dbEmps as $emp) {
            $eid = $emp['employee_id'] ?? null;
            if (!$eid || !str_starts_with($eid, $company)) continue;
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

        // ── Calculate stats per employee ────────────────────────────────────
        $cutoff = self::WORK_START; // '08:30'
        $results = [];

        foreach ($employees as $eid => $emp) {
            $presentDays      = 0;
            $lateCount        = 0;
            $totalLateMinutes = 0;
            $dailyRecords     = [];

            foreach ($workingDays as $wd) {
                if (isset($emp['dates'][$wd])) {
                    $ci = $emp['dates'][$wd]; // e.g. "8:32"
                    $presentDays++;

                    // Normalise time: "8:32" → "08:32"
                    $ciNorm = strlen($ci) === 4 ? "0{$ci}" : $ci;
                    $isLate = $ciNorm > $cutoff;
                    $lateMins = 0;
                    if ($isLate) {
                        $lateCount++;
                        $ciCarbon   = Carbon::parse($wd . ' ' . $ciNorm);
                        $cutCarbon  = Carbon::parse($wd . ' ' . $cutoff);
                        $lateMins   = (int) $ciCarbon->diffInMinutes($cutCarbon);
                        $totalLateMinutes += $lateMins;
                    }
                    $dailyRecords[$wd] = [
                        'status'       => 'present',
                        'clock_in'     => $ciNorm,
                        'is_late'      => $isLate,
                        'late_minutes' => $lateMins,
                    ];
                } else {
                    $dailyRecords[$wd] = [
                        'status'       => 'absent',
                        'clock_in'     => null,
                        'is_late'      => false,
                        'late_minutes' => 0,
                    ];
                }
            }

            $absentDays = $totalWorkingDays - $presentDays;

            $results[$eid] = [
                'internal_id'        => $eid,
                'db_employee_id'     => $emp['db_employee_id'] ?? null,
                'name'               => $emp['name'],
                'preferred_name'     => $emp['preferred_name'],
                'email'              => $emp['email'],
                'department'         => $emp['department'] ?? '',
                'working_days'       => $totalWorkingDays,
                'present_days'       => $presentDays,
                'absent_days'        => $absentDays,
                'late_count'         => $lateCount,
                'total_late_minutes' => $totalLateMinutes,
                'mc_days'            => 0,
                'al_days'            => 0,
                'other_leave_days'   => 0,
                'daily'              => $dailyRecords,
            ];
        }

        // Sort by department then name
        uasort($results, fn($a, $b) =>
            strcmp($a['department'] . $a['name'], $b['department'] . $b['name'])
        );

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
        ]);
    }

    public function save(Request $request, SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid')) return response()->json(['error' => 'Unauthenticated'], 401);

        if (!$this->authorise()) {
            return response()->json(['error' => 'Access restricted to SLT and VP only.'], 403);
        }

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

            // Upsert via Supabase REST API
            $supabase->upsert('attendance_summary', $payload, 'internal_id,month,year');
        }

        return response()->json(['success' => true, 'count' => count($data['records'])]);
    }
}
