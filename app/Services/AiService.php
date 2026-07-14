<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    protected string $apiKey;
    protected string $model = 'gpt-5.4-mini';

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY', '');
    }

    private function request()
    {
        return Http::timeout(30)->connectTimeout(10)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SUGGEST KPI DESCRIPTION
    |--------------------------------------------------------------------------
    */

    public function suggestKpiDescription(
        string $kpiTitle,
        string $department = '',
        string $role = ''
    ): string {
        $context = implode(', ', array_filter([
            $department ? "Department: $department" : '',
            $role       ? "Role: $role"             : '',
        ]));

        $systemPrompt = 'You are a professional KPI consultant specializing in performance management. '
            . 'Write concise, measurable, and business-focused KPI descriptions. '
            . 'Respond with the description only — no headings, no bullet points, no extra commentary.';

        $userPrompt = "Write a professional KPI description (2–4 sentences) for this KPI title: \"$kpiTitle\"."
            . ($context ? "\nContext: $context." : '')
            . "\n\nThe description should explain what success looks like, how it will be measured, and why it matters.";

        $response = $this->request()->post('https://api.openai.com/v1/chat/completions', [
            'model'      => $this->model,
            'max_completion_tokens' => 300,
            'messages'   => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI request failed: ' . $response->body());
        }

        return trim(
            $response->json('choices.0.message.content', '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CHAT BOT
    |--------------------------------------------------------------------------
    */

    public function chat(array $messages, array $employee = [], string $jobDescription = ''): string
    {
        $userContext = '';

        if (!empty($employee)) {
            $name       = $employee['short_name'] ?? $employee['full_name'] ?? null;
            $role       = $employee['role']        ?? null;
            $department = $employee['department']  ?? null;

            $parts = array_filter([
                $name       ? "Name: $name"             : null,
                $role       ? "Role: $role"              : null,
                $department ? "Department: $department"  : null,
            ]);

            if ($parts) {
                $userContext = "\n\nCURRENT USER:\n" . implode("\n", $parts)
                    . "\nYou already know who this user is — do not ask for their name, role, or department. Address them by first name when appropriate and tailor your coaching to their role and department context.";
            }
        }

        if (!empty($jobDescription)) {
            $userContext .= "\n\nUSER'S JOB DESCRIPTION:\n" . $jobDescription
                . "\nUse this to guide KPI suggestions that are relevant to their actual responsibilities. When coaching, reference specific duties from their job description to help them build meaningful, aligned KPIs.";
        }

        $system = <<<PROMPT
You are ANIRA, the KPI AI Assistant for RGHB KPI Dashboard — an internal performance management system. Your name is ANIRA.

HOW THE SYSTEM WORKS:
- Employees are organised by role: EXECUTIVE, MANAGER, VP, SLT (Senior Leadership Team).
- Each employee sets KPIs for the current financial year, split into 4 quarters (Q1–Q4).
- KPIs have a title, description, category, weightage (%), and quarterly targets + actuals.
- The overall KPI score is calculated from achievement % weighted by each KPI's weightage.
- Sensitive changes (editing a KPI, changing targets, deleting) require approval from the line manager.
- The approval chain: EXECUTIVE → MANAGER → VP → SLT.
- The Approval Center (/approval) shows pending requests to approve or reject.
- The Dashboard (/dashboard) shows overall scores, department rankings, and status breakdowns.
- Weightage (/weightage) lets you adjust the % weight of each KPI (must total 100%).
- Quarter completion requires uploading a proof document.
- The default login password is set by the company admin.
- KPI Linkages allow managers and above to cascade targets down to their team members. A manager can assign a specific target (number, currency, or percentage) for a KPI category/sub-category to a subordinate. This creates a formal link between the manager's KPI and the team member's KPI, ensuring alignment. Linkages can be created or removed from the KPI creation page. Only the person who created a linkage can remove it.
- Activity Log (/activity-log) shows a full history of all actions in the system — KPI creation, edits, approvals, rejections, quarter completions, and more. It helps users track what has happened to their KPIs over time.

YOUR ROLE AS A KPI ADVISOR AND COACH:
You are both a KPI advisor and a coach. You give concrete, personalised suggestions AND help users think deeply about their performance goals.

When helping a user build a KPI:
- Give specific examples of strong KPI titles, descriptions, and targets that fit their role, department, and job description. Do not be vague — suggest real numbers, real measures, and real outcomes.
- Explain WHY each suggestion works well (what makes it measurable, ambitious, and relevant).
- Invite them to adapt the suggestion to their actual work rather than copying it blindly.
- Ask follow-up questions to personalise further if you need more context.

ADVISORY APPROACH — follow this flow when building a KPI with a user:

STEP 1 - UNDERSTAND THEIR CONTEXT
If you already know their role, department, and job description, use that immediately to suggest relevant KPI areas. Ask only if you need more detail.

STEP 2 - SUGGEST AND REFINE THE OUTCOME
Based on their context, suggest 2-3 concrete KPI ideas that would be strong for their role. For each, explain the outcome it targets and why it matters.
Ask which resonates most, or whether they have a different goal in mind.

STEP 3 - DEFINE AND CONFIRM THE MEASURE
Suggest a specific measurement method for the chosen KPI (e.g. "measured as monthly revenue in RM tracked in the finance system").
Confirm or refine with the user.

STEP 4 - RECOMMEND TARGETS
Suggest a base target and stretch target with a clear rationale (e.g. "based on last year's performance, a base of RM 500,000 is realistic, with a stretch of RM 650,000 for outstanding performance").
Suggest quarterly splits that show realistic progression (e.g. lower in Q1 as setup, ramping in Q3-Q4).

STEP 5 - CRAFT THE DESCRIPTION
Write a strong KPI description for the user as a suggestion — covering what is measured, how it is tracked, and why it matters.
Invite them to edit it to match their actual situation before finalising.

ADVISORY PRINCIPLES:
- Be direct and specific. Vague coaching frustrates users — give them something concrete to work with.
- Always tailor suggestions to their job description, role, and department if that information is available.
- Explain your reasoning — don't just state a suggestion, say why it's a good KPI.
- Keep responses focused — suggest one thing at a time rather than overwhelming them with options.
- If a user accepts your suggestion, affirm it and move to the next step.

KPI LINKAGES ADVISORY:
If the user is a MANAGER, VP, or SLT, after finalising their KPI suggest which part of the target could be cascaded to a team member as a linkage. Describe what the linked target should be and why it creates alignment.

GENERAL QUESTIONS:
If the user asks a general system question (how approvals work, how to navigate the site, linkages, activity log, etc.), answer it directly and clearly.

FORMATTING AND LENGTH:
Keep every response short — 3 to 5 sentences maximum. No long paragraphs. No walls of text.
If you need a list, keep it to 3 items at most.
Do not use markdown — no asterisks for bold, no backticks. For lists use plain numbered lines (1. 2. 3.) or plain dash bullets (- item).
Respond like a sharp, confident advisor — concise, direct, and clear.
PROMPT;

        $response = $this->request()->post('https://api.openai.com/v1/chat/completions', [
            'model'                 => $this->model,
            'max_completion_tokens' => 200,
            'messages'              => array_merge(
                [['role' => 'system', 'content' => $system . $userContext]],
                $messages
            ),
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI request failed: ' . $response->body());
        }

        return trim(
            $response->json('choices.0.message.content', 'Sorry, I could not respond right now.')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCORE KPI DESCRIPTION
    |--------------------------------------------------------------------------
    */

    public function scoreKpiDescription(
        string  $kpiTitle,
        string  $description,
        mixed   $baseTarget     = null,
        mixed   $stretchTarget  = null,
        ?string $unit           = null,
        mixed   $weightage      = null,
        ?string $category       = null,
        ?string $subCategory    = null,
        ?array  $quarterTargets = null
    ): array {
        $systemPrompt = 'You are a KPI quality evaluator. Respond ONLY with valid JSON — no markdown, no explanation.';

        $unitLabel = match($unit) {
            'currency'   => 'RM',
            'percentage' => '%',
            default      => '',
        };

        $details  = "KPI Title: \"$kpiTitle\"";
        $details .= "\nDescription: \"$description\"";

        if ($category)    $details .= "\nCategory: $category";
        if ($subCategory) $details .= "\nSub-Category: $subCategory";
        if ($baseTarget !== null)    $details .= "\nBase Target: $baseTarget$unitLabel";
        if ($stretchTarget !== null) $details .= "\nStretch Target: $stretchTarget$unitLabel";
        if ($weightage !== null)     $details .= "\nWeightage: $weightage%";

        if (!empty($quarterTargets)) {
            $qtText = implode(', ', array_map(
                fn($v, $i) => 'Q' . ($i + 1) . ': ' . $v . $unitLabel,
                $quarterTargets,
                array_keys($quarterTargets)
            ));
            $details .= "\nQuarterly Targets: $qtText";

            if ($unit === 'percentage') {
                $details .= "\nNote: This is a rate/percentage KPI — quarterly targets represent the target rate per quarter, NOT cumulative sums. Good quarterly targets should show a progressive trend toward the annual base target by Q4.";
            } else {
                $qtSum = array_sum($quarterTargets);
                $details .= "\nSum of Quarterly Targets: $qtSum$unitLabel (annual base target is $baseTarget$unitLabel)";
                $details .= $qtSum >= (float)$baseTarget
                    ? " — quarterly sum meets or exceeds the base target, which is good."
                    : " — WARNING: quarterly sum is below the annual base target, which means the plan will fall short.";
            }
        }

        $userPrompt = "Score this KPI out of 10 across these dimensions:\n"
            . "1. Title clarity — specific and action-oriented?\n"
            . "2. Description quality — clear, measurable, explains how it is tracked and why it matters?\n"
            . "3. Target ambition — is the base target meaningful? Is the stretch target a genuine stretch?\n"
            . "4. Quarterly distribution — do quarterly targets add up to the annual base and show realistic progression?\n"
            . "5. Overall coherence — do title, description, category, and targets tell a consistent story?\n\n"
            . "SCORING GUIDE (be fair and encouraging, not overly harsh):\n"
            . "10 — Perfect: all 5 dimensions are excellent, nothing to improve\n"
            . "8-9 — Strong: most dimensions are well done, only minor improvements needed\n"
            . "6-7 — Good: solid effort with 1-2 dimensions that need improvement\n"
            . "4-5 — Fair: the idea is there but key details are missing (e.g. no measurement method, vague targets)\n"
            . "1-3 — Weak: major gaps across multiple dimensions\n\n"
            . "Important: if the title is specific, the description explains the measurement method, and targets are set — this should score at least 7. Reserve scores below 5 for genuinely poor KPIs.\n\n"
            . $details
            . "\n\nRespond with JSON only: {\"score\": number, \"feedback\": \"one short sentence on the single most important thing to improve\"}";

        $response = $this->request()->post('https://api.openai.com/v1/chat/completions', [
            'model'                 => $this->model,
            'max_completion_tokens' => 120,
            'temperature'           => 0.2,
            'messages'              => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI request failed: ' . $response->body());
        }

        $text = trim($response->json('choices.0.message.content', '{}'));

        return json_decode($text, true) ?? ['score' => 0, 'feedback' => ''];
    }

    /*
    |--------------------------------------------------------------------------
    | SUGGEST QUARTERLY TARGETS
    |--------------------------------------------------------------------------
    */

    public function suggestQuarterlyTargets(
        string $kpiTitle,
        float  $annualTarget,
        string $unit = ''
    ): array {
        $systemPrompt = 'You are a KPI planning expert. Respond ONLY with valid JSON — no markdown, no explanation.';

        $userPrompt = "Split an annual KPI target of $annualTarget $unit into 4 quarterly targets for: \"$kpiTitle\"."
            . "\nConsider realistic growth progression (not always equal splits)."
            . "\nRespond with JSON only: {\"q1\": number, \"q2\": number, \"q3\": number, \"q4\": number}";

        $response = $this->request()->post('https://api.openai.com/v1/chat/completions', [
            'model'       => $this->model,
            'max_completion_tokens'  => 100,
            'temperature' => 0.3,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI request failed: ' . $response->body());
        }

        $text = trim($response->json('choices.0.message.content', '{}'));

        return json_decode($text, true) ?? [];
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE PERFORMANCE REVIEW (weekly / monthly / quarterly)
    |--------------------------------------------------------------------------
    */

    public function generatePerformanceReview(
        string $employeeName,
        string $periodType,
        string $periodLabel,
        array  $stats
    ): array {
        $systemPrompt = 'You are a professional performance-review assistant for an internal company KPI system. '
            . 'Write objective, evidence-based commentary in a neutral, professional tone — no emoji, no exclamation marks, no motivational fluff. '
            . 'Base the review strictly on the activity and KPI data provided. Respond ONLY with valid JSON — no markdown, no explanation.';

        $activeDays = $stats['active_days'] ?? 0;
        $totalDays  = $stats['total_days'] ?? 0;
        $tasks      = $stats['tasks'] ?? [];
        $kpis       = $stats['kpis'] ?? [];

        $taskLines = empty($tasks)
            ? 'No task updates were logged during this period.'
            : implode("\n", array_map(
                fn($t) => "- \"{$t['title']}\": logged " . ($t['delta_in_period'] >= 0 ? '+' : '') . "{$t['delta_in_period']} {$t['unit']} this period, running total {$t['actual']}/{$t['target']} {$t['unit']} ({$t['status']})",
                $tasks
            ));

        $kpiLines = empty($kpis)
            ? 'No KPIs are set for this employee.'
            : implode("\n", array_map(
                fn($k) => "- \"{$k['kpi_title']}\" ({$k['category']}): {$k['achievement_percentage']}% of annual target achieved, status {$k['status']}",
                $kpis
            ));

        $userPrompt = "Employee: {$employeeName}\n"
            . "Review period: {$periodLabel} ({$periodType})\n\n"
            . "TASK ACTIVITY:\n"
            . "Active on {$activeDays} of {$totalDays} days in this period.\n"
            . "{$taskLines}\n\n"
            . "KPI STANDING (current, not period-specific):\n"
            . "{$kpiLines}\n\n"
            . "Score this period 0–100, weighing both task activity consistency and KPI achievement:\n"
            . "90-100 — Highly consistent activity and strong KPI achievement.\n"
            . "75-89 — Solid, mostly consistent activity with KPIs on track.\n"
            . "50-74 — Inconsistent activity or KPI achievement noticeably behind pace.\n"
            . "0-49 — Little to no logged activity and/or KPI achievement well behind.\n\n"
            . "Respond with JSON only: {\"score\": number, \"narrative\": \"2-4 professional sentences summarizing performance this period, naming what is going well and what needs attention, grounded in the data above\"}";

        $response = $this->request()->post('https://api.openai.com/v1/chat/completions', [
            'model'                 => $this->model,
            'max_completion_tokens' => 300,
            'temperature'           => 0.3,
            'messages'              => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI request failed: ' . $response->body());
        }

        $text = trim($response->json('choices.0.message.content', '{}'));

        return json_decode($text, true) ?? ['score' => 0, 'narrative' => ''];
    }

    /*
    |--------------------------------------------------------------------------
    | SUGGEST COMPLETE KPI (from coaching conversation)
    |--------------------------------------------------------------------------
    */

    public function suggestKpi(array $messages, array $employee = [], string $jobDescription = ''): array
    {
        $name       = $employee['short_name'] ?? $employee['full_name'] ?? 'the user';
        $role       = $employee['role']        ?? '';
        $department = $employee['department']  ?? '';

        $employeeCtx = implode(', ', array_filter([
            $role       ? "Role: $role"             : '',
            $department ? "Department: $department" : '',
        ]));

        $jdCtx = $jobDescription
            ? "\n\nJOB DESCRIPTION:\n$jobDescription"
            : '';

        $systemPrompt = "You are a KPI planning expert. Based on the coaching conversation below, generate a complete, high-quality KPI that best fits the employee's role, department, and job description. "
            . "Choose values that will score highly: specific title, clear measurable description, ambitious but realistic targets, and well-distributed quarterly targets. "
            . "Employee context: $employeeCtx$jdCtx\n\n"
            . "VALID CATEGORIES AND SUB-CATEGORIES (use exactly these values):\n"
            . "- Financial: sub_category must be 'Revenue' or 'Operating Cost Optimisation'\n"
            . "- Growth & Customer: sub_category must be 'New Customer Acquisition' or 'Growth'\n"
            . "- Initiatives: sub_category must be 'Continuous Improvement & New Business'\n"
            . "- People: sub_category must be 'Certification of Competence (COC)' or 'Staff Development'\n\n"
            . "VALID UNITS: 'number', 'currency', 'percentage'\n\n"
            . "Respond ONLY with valid JSON — no markdown, no explanation outside the JSON.";

        $conversationSummary = implode("\n", array_map(
            fn($m) => strtoupper($m['role']) . ': ' . $m['content'],
            $messages
        ));

        $userPrompt = "Coaching conversation:\n\n$conversationSummary\n\n"
            . "Based on this conversation, output the single best KPI as JSON with ALL fields:\n"
            . '{'
            . '"title":"specific action-oriented KPI title",'
            . '"description":"2-3 sentences: what is measured, how it is tracked, and why it matters",'
            . '"category":"Financial|Growth & Customer|Initiatives|People",'
            . '"sub_category":"exact value from list above",'
            . '"unit":"number|currency|percentage",'
            . '"base_target":number,'
            . '"stretch_target":number,'
            . '"weightage":number (suggested % weight, e.g. 20),'
            . '"q1":number,"q2":number,"q3":number,"q4":number,'
            . '"q1_title":"short title for Q1 plan (e.g. Launch Phase: establish baseline and initial activities)",'
            . '"q1_description":"what will be done in Q1 to progress toward the target",'
            . '"q2_title":"short title for Q2 plan",'
            . '"q2_description":"what will be done in Q2",'
            . '"q3_title":"short title for Q3 plan",'
            . '"q3_description":"what will be done in Q3",'
            . '"q4_title":"short title for Q4 plan",'
            . '"q4_description":"what will be done in Q4 to close out the year",'
            . '"rationale":"1 sentence on why this KPI fits their role and job description"'
            . '}';

        $response = $this->request()->post('https://api.openai.com/v1/chat/completions', [
            'model'                 => $this->model,
            'max_completion_tokens' => 900,
            'temperature'           => 0.3,
            'messages'              => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI request failed: ' . $response->body());
        }

        $text = trim($response->json('choices.0.message.content', '{}'));

        // strip possible markdown code fences
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        return json_decode(trim($text), true) ?? [];
    }
}
