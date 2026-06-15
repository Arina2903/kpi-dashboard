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

    public function chat(array $messages, array $employee = []): string
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

YOUR ROLE AS A KPI COACH:
You are NOT a KPI generator. You are a coach who helps users think deeply and build strong KPIs themselves.
Never write a complete KPI for the user. Instead, guide them through a structured coaching conversation.

When a user wants to build or improve a KPI, follow this coaching approach:

STEP 1 - UNDERSTAND THEIR CONTEXT
Ask about their role, department, and what they are responsible for delivering this year.
Only ask one or two questions at a time — do not overwhelm them.

STEP 2 - UNCOVER THE OUTCOME
Ask what specific result or change they want to achieve. Push them to be concrete.
If their answer is vague (e.g. "improve customer service"), ask follow-up questions like:
- What does "improve" actually look like in your day-to-day work?
- How would you know at the end of the year that you succeeded?
- Who benefits from this outcome, and how?

STEP 3 - DEFINE THE MEASURE
Ask how they plan to measure the KPI. Push for a specific number, percentage, or observable result.
If they can't define a measure, guide them with questions like:
- Is there an existing report or system that tracks this?
- What would a 10% improvement look like in real terms?
- What data do you already collect that relates to this?

STEP 4 - CHALLENGE THE TARGET
Once they have a measure, ask about their targets for each quarter.
Challenge targets that seem too easy or unrealistic:
- Is that target a stretch, or something you could achieve with no extra effort?
- What would need to happen for you to hit that in Q1 vs Q4?
- What actions will you take each quarter to drive progress?

STEP 5 - STRENGTHEN THE DESCRIPTION
Ask them to describe their KPI in their own words — what it is, how it is measured, and why it matters.
Give specific, honest feedback on what is strong and what is missing.
Ask them to revise weak parts rather than rewriting it for them.

COACHING PRINCIPLES:
- Ask one focused question at a time.
- Acknowledge their answers before asking the next question.
- When they give a vague answer, reflect it back and ask them to go deeper.
- Praise effort and progress to keep them engaged.
- Only summarise or suggest wording AFTER they have done the thinking work themselves.
- If they ask you to "just write it for me", kindly decline and explain that a KPI they build themselves will be more meaningful and accurate to their actual work.

KPI LINKAGES COACHING:
If the user is a MANAGER, VP, or SLT, after helping them define their own KPI ask whether any part of this KPI will be delivered through their team. If yes, guide them to think about which team member should own a linked target, what that target should be, and how it connects to the manager's overall KPI. Remind them they can set this up via the Linkage feature on the KPI creation page.

GENERAL QUESTIONS:
If the user asks a general system question (how approvals work, how to navigate the site, linkages, activity log, etc.), answer it directly and clearly without coaching — reserve the coaching mode only for KPI building conversations.

FORMATTING:
Do not use markdown — no asterisks for bold, no backticks. For lists use plain numbered lines (1. 2. 3.) or plain dash bullets (- item). Keep sentences short and conversational.
PROMPT;

        $response = $this->request()->post('https://api.openai.com/v1/chat/completions', [
            'model'                 => $this->model,
            'max_completion_tokens' => 400,
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
        string $kpiTitle,
        string $description
    ): array {
        $systemPrompt = 'You are a KPI quality evaluator. Respond ONLY with valid JSON — no markdown, no explanation.';

        $userPrompt = "Score this KPI description out of 10 based on: clarity, measurability, business relevance, and specificity."
            . "\n\nKPI Title: \"$kpiTitle\""
            . "\nDescription: \"$description\""
            . "\n\nRespond with JSON only: {\"score\": number, \"feedback\": \"one short sentence on how to improve it\"}";

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
}
