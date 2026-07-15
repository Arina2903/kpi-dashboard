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
    <script src="https://cdn.ckeditor.com/ckeditor5/19.0.0/classic/ckeditor.js"></script>
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
        .jd-editor .ck-editor__editable_inline { min-height: 120px; font-size: 12.5px; }
        .jd-editor .ck-content table { width: 100%; border-collapse: collapse; }
        .jd-editor .ck-content table td, .jd-editor .ck-content table th { border: 1px solid #94a3b8; padding: 6px 8px; }
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

        $jdStatus = $jobDescription['status'] ?? 'draft';
    @endphp

    <div class="flex items-center justify-end">
        @if($jdStatus === 'submitted')
        <span class="text-[9px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">
            Submitted{{ !empty($jobDescription['submitted_at']) ? ' — ' . \Carbon\Carbon::parse($jobDescription['submitted_at'])->format('d M Y') : '' }}
        </span>
        @else
        <span class="text-[9px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full bg-slate-200 text-slate-600">
            Draft
        </span>
        @endif
    </div>

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
        <div class="doc-bar text-[11px] px-4 py-2.5 border-t-2 border-black">
            {!! $section['title'] !!}
        </div>
        <div class="p-4 {{ $i < count($jdSections) - 1 ? 'border-b border-black' : '' }}">
            <div class="jd-editor bg-white border border-slate-300 rounded-lg">
                <div id="editor-{{ $section['key'] }}">{!! $jobDescription[$section['key']] ?? '' !!}</div>
            </div>
            <input type="hidden" name="{{ $section['key'] }}" id="input-{{ $section['key'] }}">
        </div>
        @endforeach

        @php
            $ackDate = !empty($jobDescription['submitted_at'])
                ? \Carbon\Carbon::parse($jobDescription['submitted_at'])->format('d/m/Y')
                : '';
            $jobholderName = $user['short_name'] ?? $user['full_name'] ?? $user['name'] ?? '-';
        @endphp

        <div class="doc-bar text-[11px] px-4 py-2.5 border-t-2 border-black">
            5) Acknowledgement
        </div>
        <div class="p-4 border-b border-black">
            <p class="text-[11px] italic text-slate-700 leading-relaxed mb-4">
                I hereby confirm that I have received, read, and understood the contents of this Job Description.
                I agree to perform the duties and responsibilities stated herein in accordance with the Company's policies, SOPs, and Management's instructions from time to time.
            </p>
            <table class="w-full text-left border-collapse border border-black">
                <thead>
                    <tr>
                        <th class="border border-black p-2 text-[10px] font-black uppercase w-1/3">HR &amp; Administration</th>
                        <th class="border border-black p-2 text-[10px] font-black uppercase w-1/3">Jobholder</th>
                        <th class="border border-black p-2 text-[10px] font-black uppercase w-1/3">Supervisor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-black p-3 align-top" style="height:110px;">
                            <div style="height:56px;"></div>
                            <p class="text-[11px] text-slate-700">Name :</p>
                            <p class="text-[11px] text-slate-700">Date :</p>
                        </td>
                        <td class="border border-black p-3 align-top" style="height:110px;">
                            <div style="height:56px;"></div>
                            <p class="text-[11px] text-slate-700">Name : {{ $jobholderName }}</p>
                            <p class="text-[11px] text-slate-700">Date : {{ $ackDate }}</p>
                        </td>
                        <td class="border border-black p-3 align-top" style="height:110px;">
                            <div style="height:56px;"></div>
                            <p class="text-[11px] text-slate-700">Name : {{ $reportingTo }}</p>
                            <p class="text-[11px] text-slate-700">Date : {{ $ackDate }}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-3 flex justify-end gap-2 border-t-2 border-black">
            <button
                type="submit"
                name="action"
                value="draft"
                class="text-[11px] font-black px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition"
            >
                Save Draft
            </button>
            <button
                type="submit"
                name="action"
                value="submit"
                class="text-[11px] font-black px-4 py-2 rounded-lg bg-black text-white hover:bg-slate-800 transition"
            >
                Submit
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
        ClassicEditor
            .create(document.getElementById('editor-' + name), {
                placeholder: placeholder,
                toolbar: ['bold', 'italic', 'bulletedList', 'numberedList', '|', 'insertTable'],
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
                },
            })
            .then(function (editor) {
                jdEditors[name] = editor;
            })
            .catch(function (error) {
                console.error('JD editor failed to load:', name, error);
            });
    }

    initJdEditor('summary', 'Describe the purpose of this role...');
    initJdEditor('responsibilities', 'List the key responsibilities...');
    initJdEditor('requirements', 'List qualifications and experience...');
    initJdEditor('competencies', 'List competencies and behavioural expectations...');

    document.getElementById('jdForm').addEventListener('submit', function () {
        Object.keys(jdEditors).forEach(function (name) {
            document.getElementById('input-' + name).value = jdEditors[name].getData();
        });
    });
</script>

</body>
</html>
