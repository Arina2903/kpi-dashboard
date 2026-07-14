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
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.min.js"></script>
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
        .jd-editor .ql-editor { font-size: 12.5px; min-height: 120px; }
        .jd-editor .ql-editor table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .jd-editor .ql-editor table td { border: 1px solid #94a3b8; padding: 6px 8px; }
    </style>
</head>
<body class="bg-[#f0f2f7] min-h-screen text-slate-900">

@include('partials.sidebar')

<main id="mainContent" class="ml-[230px] min-h-screen transition-all duration-300">
<div class="p-4 max-w-4xl mx-auto space-y-3">

    <a href="/dashboard" class="text-[10px] text-slate-500 hover:text-slate-800">← Dashboard</a>

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

    <form method="POST" action="{{ route('job-description.update') }}" id="jdForm">
    @csrf

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

        @php
            $jdSections = [
                ['key' => 'summary',          'title' => '1) Job Purpose',                             'placeholder' => 'Describe the purpose of this role...'],
                ['key' => 'responsibilities', 'title' => '2) Key Responsibilities',                     'placeholder' => 'List the key responsibilities...'],
                ['key' => 'requirements',     'title' => '3) Qualifications &amp; Experience',           'placeholder' => 'List qualifications and experience...'],
                ['key' => 'competencies',     'title' => '4) Competencies &amp; Behavioural Expectations', 'placeholder' => 'List competencies and behavioural expectations...'],
            ];
        @endphp

        @foreach($jdSections as $i => $section)
        <div class="doc-bar text-sm px-4 py-2.5 border-t-2 border-black">
            {!! $section['title'] !!}
        </div>
        <div class="p-4 {{ $i < count($jdSections) - 1 ? 'border-b border-black' : '' }}">
            <div class="flex justify-end mb-1.5">
                <button
                    type="button"
                    onclick="insertJdTable('{{ $section['key'] }}')"
                    class="text-[10px] font-black px-2 py-1 rounded border border-slate-300 text-slate-600 hover:bg-slate-50 transition"
                >
                    + Insert Table
                </button>
            </div>
            <div class="jd-editor bg-white border border-slate-300 rounded-lg" id="editor-{{ $section['key'] }}"></div>
            <input type="hidden" name="{{ $section['key'] }}" id="input-{{ $section['key'] }}" value="{{ $jobDescription[$section['key']] ?? '' }}">
        </div>
        @endforeach

        <div class="p-3 flex justify-end border-t-2 border-black">
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
    var jdEditors = {};

    function initJdEditor(name, placeholder) {
        var quill = new Quill('#editor-' + name, {
            theme: 'snow',
            placeholder: placeholder,
            modules: {
                toolbar: [
                    ['bold', 'italic'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                ],
            },
        });

        var existing = document.getElementById('input-' + name).value;
        if (existing) {
            quill.clipboard.dangerouslyPasteHTML(existing);
        }

        jdEditors[name] = quill;
    }

    function insertJdTable(name) {
        var quill = jdEditors[name];
        var range = quill.getSelection(true);
        var tableHtml = '<table><tr><td>Cell</td><td>Cell</td></tr><tr><td>Cell</td><td>Cell</td></tr></table><p><br></p>';
        quill.clipboard.dangerouslyPasteHTML(range.index, tableHtml);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initJdEditor('summary', 'Describe the purpose of this role...');
        initJdEditor('responsibilities', 'List the key responsibilities...');
        initJdEditor('requirements', 'List qualifications and experience...');
        initJdEditor('competencies', 'List competencies and behavioural expectations...');
    });

    document.getElementById('jdForm').addEventListener('submit', function () {
        Object.keys(jdEditors).forEach(function (name) {
            document.getElementById('input-' + name).value = jdEditors[name].root.innerHTML;
        });
    });
</script>

</body>
</html>
