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

    <a href="/dashboard" class="text-[10px] text-slate-500 hover:text-slate-800">← Dashboard</a>

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

    {{-- DOCUMENT --}}
    <div class="bg-white doc-card border-2 border-black overflow-hidden">

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

        @if(empty($jobDescription))

        {{-- EMPTY STATE --}}
        <div class="p-8 text-center border-t-2 border-black">
            <p class="text-[13px] font-black text-slate-800">No job description content on file yet</p>
            <p class="text-[12px] text-slate-500 mt-1 max-w-sm mx-auto">
                Please contact HR or your department admin to have the job purpose, responsibilities, and requirements added.
            </p>
        </div>

        @else

            @if(!empty($jobDescription['summary']))
            <div class="doc-bar text-sm px-4 py-2.5 border-t-2 border-black">
                1) Job Purpose
            </div>
            <div class="p-4 border-b border-black">
                <p class="text-[12.5px] text-slate-700 leading-relaxed whitespace-pre-line">
                    {{ $jobDescription['summary'] }}
                </p>
            </div>
            @endif

            @if(!empty($jobDescription['responsibilities']))
            <div class="doc-bar text-sm px-4 py-2.5 border-t-2 border-black">
                2) Key Responsibilities
            </div>
            <div class="p-4 border-b border-black">
                <ol class="list-decimal pl-5 space-y-2">
                    @foreach($jobDescription['responsibilities'] as $item)
                    <li class="text-[12.5px] text-slate-700 leading-relaxed">{{ $item }}</li>
                    @endforeach
                </ol>
            </div>
            @endif

            @if(!empty($jobDescription['requirements']))
            <div class="doc-bar text-sm px-4 py-2.5 border-t-2 border-black">
                3) Qualifications &amp; Experience
            </div>
            <div class="p-4 border-b border-black">
                <ul class="list-disc pl-5 space-y-2">
                    @foreach($jobDescription['requirements'] as $item)
                    <li class="text-[12.5px] text-slate-700 leading-relaxed">{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(!empty($jobDescription['competencies']))
            <div class="doc-bar text-sm px-4 py-2.5 border-t-2 border-black">
                4) Competencies &amp; Behavioural Expectations
            </div>
            <div class="p-4">
                <ul class="list-disc pl-5 space-y-2">
                    @foreach($jobDescription['competencies'] as $item)
                    <li class="text-[12.5px] text-slate-700 leading-relaxed">{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

        @endif

    </div>

    @if(!empty($jobDescription['updated_at']))
    <p class="text-[10px] text-slate-400 text-right pr-1">
        Last updated {{ \Carbon\Carbon::parse($jobDescription['updated_at'])->format('d M Y, h:i A') }}
    </p>
    @endif

</div>
</main>

</body>
</html>
