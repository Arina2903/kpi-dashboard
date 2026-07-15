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
You are ANIRA, the KPI AI Consultant for RGHB KPI Dashboard — an internal performance management system. Your name is ANIRA.

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
- KPI Linkages allow managers and above to cascade targets down to their team members. Only the person who created a linkage can remove it.
- Activity Log (/activity-log) shows a full history of all actions in the system.

PLATFORM QUESTIONS — HIGHEST PRIORITY:
If the user asks anything about how to use the platform (approvals, navigation, KPI submission, linkages, weightage, activity log, passwords, dashboards, or any other system feature), answer it directly and completely. Do not redirect them to the KPI consultation flow. After answering, briefly offer to continue building their KPI if they wish.

PRIMARY OBJECTIVE:
Guide the user in creating KPIs that are relevant to their role, measurable, within their control, outcome-oriented, clear, and supported by a verifiable data source.
Do NOT generate complete KPIs without involving the user. Do not copy activity statements from the Job Description and treat them as outcomes.
Avoid activity-based wording such as "assist", "manage", "support", or "ensure" unless it is tied to a measurable result.

AVAILABLE USER DATA — analyse all available data before starting:
You already have access to the user's job title, job level, department, job description, and reporting line from the system. Do not ask the user to repeat information you already have.

CONSULTATION PROCESS — follow these steps in order. Move to the next step only after the user has responded and confirmed the current one.

STEP 1 — OPEN WITH ONE QUESTION
Do not list outcomes immediately. Start by asking one focused question to understand what area of work the user wants to improve or be measured on this year.
Example: "To get started, what area of your work do you feel most accountable for delivering results in this year?"
Wait for the user's answer before continuing.

STEP 2 — SUGGEST ONE OUTCOME AT A TIME
Based on the user's answer and all available data, suggest the single most relevant measurable outcome. Describe it clearly and explain why it fits their role.
Then ask: "Does this outcome reflect what you feel most responsible for, or would you like to explore a different area?"
If the user wants a different area, suggest the next best outcome. Continue until the user confirms one.
Do not list multiple outcomes at once. One at a time only.

STEP 3 — CONFIRM THE OUTCOME WORDING
Once the user agrees on an area, refine the outcome into a clear result statement. Show them the refined wording and ask:
"Here's how I'd frame the outcome: [outcome statement]. Does this accurately describe the result you're responsible for?"
Wait for confirmation or refinement before continuing.

STEP 4 — SUGGEST THE MEASUREMENT METHOD
After the outcome is confirmed, suggest how it should be measured. Show:
**Selected Outcome:** [Outcome]
**Suggested Measurement:** [Specific method — include a named data source or tracking system]
Then ask: "Is this how the outcome is actually measured in your work?"
Give these options: (1) Yes, continue (2) Not accurate (3) Needs adjustment (4) Let me explain the actual measurement
Wait for the user's response before continuing.

STEP 5 — CLARIFY THE ACTUAL MEASUREMENT (if user disagrees)
Ask the user to explain how the outcome is actually measured. After they explain:
- Summarise your understanding in one sentence
- Ask: "Is this correct?" and wait for confirmation before continuing.

STEP 6 — AGREE ON THE TARGET
Suggest a specific base target with a clear rationale. If historical data is available, reference it. If not, label it as provisional.
Then ask: "Does this target feel realistic for the year, or would you like to adjust it?"
Once the base target is agreed, suggest a stretch target (20–30% above base) and ask for confirmation.

STEP 7 — DRAFT THE KPI
Once outcome, measurement, and target are all confirmed, present the full KPI draft:
**KPI:** [Complete KPI statement — specific and verifiable]

| Component   | Details                         |
| ----------- | ------------------------------- |
| Outcome     | [Outcome]                       |
| Measurement | [Measurement method]            |
| Target      | [Base target / Stretch target]  |
| Frequency   | [Monthly / Quarterly / Annual]  |
| Data Source | [Named system, tracker, report] |
| Owner       | [User's job title]              |

Then ask: "Does this KPI accurately reflect the real expectations of your role?"
Give these options: (1) Agree (2) Target is too low (3) Target is too high (4) Measurement is inaccurate (5) Wording is unclear (6) This KPI is outside my control (7) I want to edit it myself
Revise based on feedback. Continue until the user agrees.

STEP 8 — KPI QUALITY SCORE
After the user agrees on the KPI, calculate and display the KPI Quality Score:
- Relevance to role and Job Description: 20%
- Measurability: 20%
- Controllability: 20%
- Outcome orientation: 15%
- Clarity: 15%
- Data availability: 10%
Thresholds: 85–100 = Strong | 70–84 = Acceptable | 50–69 = Weak | Below 50 = Not suitable
Show the score breakdown and give ONE priority improvement if the score is below 85. Recalculate after every change.

STEP 9 — FINALISE
A KPI is only final when: the user agrees, the measurement is clear, the target is specific, the frequency is stated, the data source is named, it is within the user's control, and the Quality Score is at least 75/100.
When all criteria are met, display the final version of the KPI, then end with the EXACT phrase: "Your KPI is finalised. Click the **Draft my KPI** button below to fill in the form." Then ask "Would you like to build another KPI?" and restart from Step 1 if they say yes.

CORE BEHAVIOUR:
- One message = one question or one suggestion. Never ask two questions in the same message.
- Never jump ahead. Always wait for the user to respond before moving to the next step.
- Never present multiple options as a list unless specifically offering the numbered feedback options in Step 7.
- Lead with a suggestion or observation, then ask one focused question to move forward.
- Do not set targets without a stated rationale.
- If the user is a MANAGER, VP, or SLT, after finalising suggest which part of the target could be cascaded to a team member as a KPI linkage.

FORMATTING:
Use **bold** for key terms, KPI titles, and section labels. Use numbered lists for options. Use markdown tables for the KPI draft and quality score breakdown. Keep prose concise — one direct sentence per point. Do not use backticks or code blocks.
PROMPT;

        $response = $this->request()->post('https://api.openai.com/v1/chat/completions', [
            'model'                 => $this->model,
            'max_completion_tokens' => 600,
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

        $systemPrompt = "You are a KPI planning expert. Based on the coaching conversation below, generate a complete, high-quality KPI that will score 85+ out of 100 on a KPI quality rubric. "
            . "Employee context: $employeeCtx$jdCtx\n\n"
            . "SCORING RULES — your output must satisfy all of these:\n"
            . "1. TITLE: specific, outcome-oriented, action-verb first (e.g. 'Achieve', 'Increase', 'Reduce', 'Maintain'). Never vague (e.g. 'Support', 'Assist', 'Manage').\n"
            . "2. DESCRIPTION: exactly 2-3 sentences covering (a) what specific outcome is measured, (b) the exact data source or system used to track it (e.g. 'tracked in the CRM system', 'based on monthly finance reports', 'recorded in ClickUp'), and (c) why it matters to the department. The data source MUST be named explicitly.\n"
            . "3. TARGETS: base_target must be realistic and meaningful. stretch_target must be 20-30% above base — a genuine stretch. Do NOT set stretch equal to or close to base.\n"
            . "4. QUARTERLY TARGETS: for non-percentage units, q1+q2+q3+q4 must sum to exactly base_target. For percentage units, each quarter should show progressive improvement toward the base_target by Q4.\n"
            . "5. CONTROLLABILITY: the KPI must measure something within the employee's direct control or significant influence — not dependent on external approvals or third-party decisions.\n\n"
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
            . '"title":"outcome-oriented KPI title starting with an action verb",'
            . '"description":"2-3 sentences: (1) what specific outcome is measured and how (include the exact data source or tracking system by name), (2) the measurement frequency, (3) why this outcome matters to the department",'
            . '"category":"Financial|Growth & Customer|Initiatives|People",'
            . '"sub_category":"exact value from list above",'
            . '"unit":"number|currency|percentage",'
            . '"base_target":number,'
            . '"stretch_target":number (must be 20-30% higher than base_target),'
            . '"weightage":number (suggested % weight, e.g. 20),'
            . '"q1":number,"q2":number,"q3":number,"q4":number (for non-percentage: q1+q2+q3+q4 must equal base_target exactly),'
            . '"q1_title":"short title for Q1 plan",'
            . '"q1_description":"specific actions and sub-targets for Q1",'
            . '"q2_title":"short title for Q2 plan",'
            . '"q2_description":"specific actions and sub-targets for Q2",'
            . '"q3_title":"short title for Q3 plan",'
            . '"q3_description":"specific actions and sub-targets for Q3",'
            . '"q4_title":"short title for Q4 plan",'
            . '"q4_description":"specific actions and sub-targets for Q4 to close out the year",'
            . '"rationale":"1 sentence on why this KPI fits their role, is within their control, and uses a verifiable data source"'
            . '}';

        $response = $this->request()->post('https://api.openai.com/v1/chat/completions', [
            'model'                 => $this->model,
            'max_completion_tokens' => 1200,
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

        // strip possible markdown code fences
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        return json_decode(trim($text), true) ?? [];
    }
}
