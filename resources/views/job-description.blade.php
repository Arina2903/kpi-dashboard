<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Description</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .doc-card { box-shadow: 0 8px 30px rgba(15,23,42,.08); }
        .doc-bar {
            background: #0a0a0a;
            color: #fff;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen text-slate-900">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300">
<div class="p-4 max-w-4xl mx-auto space-y-3">

    <div class="flex items-center justify-between">
        <a href="/dashboard" class="text-[10px] text-slate-500 hover:text-slate-800">← Dashboard</a>
        <button
            type="button"
            id="jdEditBtn"
            onclick="setJdEditing(true)"
            class="text-[11px] font-black px-3 py-1.5 rounded-lg bg-black text-white hover:bg-slate-800 transition"
        >
            Edit Job Description
        </button>
    </div>

    @if(session('success'))
    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-2.5 text-[12px] font-semibold text-emerald-700">
        ✓ {{ session('success') }}
    </div>
    @endif

    @php
        if (session('company_code') === 'RCG') {
            $jdLogo = 'images/RCG-Logo-black.png';
        } else {
            $jdLogo = session('company_logo');
            if (!$jdLogo) {
                $logoMap = ['RGHB'=>'images/RGHB-Logo.png','RCT'=>'images/RCT-Logo.png'];
                $jdLogo = $logoMap[session('company_code')] ?? null;
            }
        }

        $position     = $jobDescription['job_title'] ?? $user['position'] ?? '-';
        $departmentNm = $department['name'] ?? $user['department_code'] ?? '-';
        $reportingTo  = $jobDescription['reporting_line'] ?? null;
        if (!$reportingTo) {
            $reportingTo = $manager['short_name'] ?? $manager['full_name'] ?? '-';
        }
        $effectiveDate = !empty($jobDescription['effective_date'])
            ? \Carbon\Carbon::parse($jobDescription['effective_date'])->format('d M Y')
            : '-';
    @endphp

    <form method="POST" action="{{ route('job-description.update') }}">
    @csrf

    {{-- DOCUMENT --}}
    <div id="jdDoc" class="bg-white doc-card border-2 border-black overflow-hidden">

        <div class="doc-bar text-sm px-4 py-2.5">
            Job Description
        </div>

        <table class="w-full text-left border-collapse table-fixed">
            <tbody>
                <tr>
                    <td rowspan="2" class="w-40 border-r-2 border-b border-black p-2 align-middle text-center">
                        @if($jdLogo)
                        <div class="h-28 flex items-center justify-center mx-auto">
                            <img
                                src="{{ asset(ltrim($jdLogo, '/')) }}"
                                alt="{{ session('company_display_name') ?: 'Company' }}"
                                class="max-w-full max-h-full object-contain"
                                onerror="this.parentElement.style.display='none';this.closest('td').querySelector('.jd-logo-fallback').classList.remove('hidden');"
                            />
                        </div>
                        <span class="jd-logo-fallback hidden text-[13px] font-black text-slate-800">
                            {{ session('company_display_name') ?: session('company_code') ?: 'Company' }}
                        </span>
                        @else
                        <span class="text-[13px] font-black text-slate-800">
                            {{ session('company_display_name') ?: session('company_code') ?: 'Company' }}
                        </span>
                        @endif
                    </td>
                    <td class="border-r border-b border-black p-3 w-1/3">
                        <p class="text-[10px] font-black text-black">Position</p>
                        <p class="text-[12px] text-slate-700 mt-1">{{ $position }}</p>
                    </td>
                    <td class="border-b border-black p-3 w-1/3">
                        <p class="text-[10px] font-black text-black">Department</p>
                        <p class="text-[12px] text-slate-700 mt-1">{{ $departmentNm }}</p>
                    </td>
                </tr>
                <tr>
                    <td class="border-r border-black p-3">
                        <p class="text-[10px] font-black text-black">Reporting To</p>
                        <p class="text-[12px] text-slate-700 mt-1">{{ $reportingTo }}</p>
                    </td>
                    <td class="p-3">
                        <p class="text-[10px] font-black text-black">Effective Date</p>
                        <p class="text-[12px] text-slate-700 mt-1">{{ $effectiveDate }}</p>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- 1) JOB PURPOSE --}}
        <div class="doc-bar text-sm px-4 py-2.5 border-t-2 border-black">
            1) Job Purpose
        </div>
        <div class="p-4 border-b border-black">
            <div class="jd-view-field">
                @if(!empty($jobDescription['summary']))
                <p class="text-[12.5px] text-slate-700 leading-relaxed whitespace-pre-line">{{ $jobDescription['summary'] }}</p>
                @else
                <p class="text-[12px] text-slate-400 italic">Not filled in yet.</p>
                @endif
            </div>
            <div class="jd-edit-field hidden">
                <textarea
                    name="summary"
                    rows="5"
                    placeholder="Describe the purpose of this role..."
                    class="w-full rounded-lg border border-slate-300 p-2.5 text-[12.5px] focus:ring-2 focus:ring-black/15 focus:border-black focus:outline-none"
                >{{ $jobDescription['summary'] ?? '' }}</textarea>
            </div>
        </div>

        {{-- 2) KEY RESPONSIBILITIES --}}
        <div class="doc-bar text-sm px-4 py-2.5 border-t-2 border-black">
            2) Key Responsibilities
        </div>
        <div class="p-4 border-b border-black">
            <div class="jd-view-field">
                @if(!empty($jobDescription['responsibilities']))
                <ol class="list-decimal pl-5 space-y-2">
                    @foreach($jobDescription['responsibilities'] as $item)
                    <li class="text-[12.5px] text-slate-700 leading-relaxed">{{ $item }}</li>
                    @endforeach
                </ol>
                @else
                <p class="text-[12px] text-slate-400 italic">Not filled in yet.</p>
                @endif
            </div>
            <div class="jd-edit-field hidden">
                <textarea
                    name="responsibilities"
                    rows="6"
                    placeholder="One responsibility per line..."
                    class="w-full rounded-lg border border-slate-300 p-2.5 text-[12.5px] focus:ring-2 focus:ring-black/15 focus:border-black focus:outline-none"
                >{{ implode("\n", $jobDescription['responsibilities'] ?? []) }}</textarea>
            </div>
        </div>

        {{-- 3) QUALIFICATIONS & EXPERIENCE --}}
        <div class="doc-bar text-sm px-4 py-2.5 border-t-2 border-black">
            3) Qualifications &amp; Experience
        </div>
        <div class="p-4 border-b border-black">
            <div class="jd-view-field">
                @if(!empty($jobDescription['requirements']))
                <ul class="list-disc pl-5 space-y-2">
                    @foreach($jobDescription['requirements'] as $item)
                    <li class="text-[12.5px] text-slate-700 leading-relaxed">{{ $item }}</li>
                    @endforeach
                </ul>
                @else
                <p class="text-[12px] text-slate-400 italic">Not filled in yet.</p>
                @endif
            </div>
            <div class="jd-edit-field hidden">
                <textarea
                    name="requirements"
                    rows="6"
                    placeholder="One qualification / experience item per line..."
                    class="w-full rounded-lg border border-slate-300 p-2.5 text-[12.5px] focus:ring-2 focus:ring-black/15 focus:border-black focus:outline-none"
                >{{ implode("\n", $jobDescription['requirements'] ?? []) }}</textarea>
            </div>
        </div>

        {{-- 4) COMPETENCIES & BEHAVIOURAL EXPECTATIONS --}}
        <div class="doc-bar text-sm px-4 py-2.5 border-t-2 border-black">
            4) Competencies &amp; Behavioural Expectations
        </div>
        <div class="p-4">
            <div class="jd-view-field">
                @if(!empty($jobDescription['competencies']))
                <ul class="list-disc pl-5 space-y-2">
                    @foreach($jobDescription['competencies'] as $item)
                    <li class="text-[12.5px] text-slate-700 leading-relaxed">{{ $item }}</li>
                    @endforeach
                </ul>
                @else
                <p class="text-[12px] text-slate-400 italic">Not filled in yet.</p>
                @endif
            </div>
            <div class="jd-edit-field hidden">
                <textarea
                    name="competencies"
                    rows="6"
                    placeholder="One competency / behavioural expectation per line..."
                    class="w-full rounded-lg border border-slate-300 p-2.5 text-[12.5px] focus:ring-2 focus:ring-black/15 focus:border-black focus:outline-none"
                >{{ implode("\n", $jobDescription['competencies'] ?? []) }}</textarea>
            </div>
        </div>

        <div class="jd-edit-field hidden p-3 flex justify-end gap-2 border-t-2 border-black">
            <button
                type="button"
                onclick="setJdEditing(false)"
                class="text-[11px] font-black px-3 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="text-[11px] font-black px-4 py-2 rounded-lg bg-black text-white hover:bg-slate-800 transition"
            >
                Save Job Description
            </button>
        </div>

    </div>
    </form>

    @if(!empty($jobDescription['updated_at']))
    <p class="text-[10px] text-slate-400 text-right pr-1">
        Last updated {{ \Carbon\Carbon::parse($jobDescription['updated_at'])->format('d M Y, h:i A') }}
    </p>
    @endif

</div>
</main>

<script>
    function setJdEditing(editing) {
        document.querySelectorAll('.jd-view-field').forEach(el => el.classList.toggle('hidden', editing));
        document.querySelectorAll('.jd-edit-field').forEach(el => el.classList.toggle('hidden', !editing));
        document.getElementById('jdEditBtn').classList.toggle('hidden', editing);
    }
</script>

</body>
</html>
