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

    public function chat(array $messages): string
    {
        $system = <<<PROMPT
You are a friendly KPI assistant for RGHB KPI Dashboard — an internal performance management system.

Here is how the system works:
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

Answer questions clearly and helpfully. If unsure, say so honestly. Keep replies concise.
Do not use markdown — no asterisks for bold, no backticks. For lists use plain numbered lines (1. 2. 3.) or plain dash bullets (- item). Keep sentences short.
PROMPT;

        $response = $this->request()->post('https://api.openai.com/v1/chat/completions', [
            'model'                 => $this->model,
            'max_completion_tokens' => 400,
            'messages'              => array_merge(
                [['role' => 'system', 'content' => $system]],
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
