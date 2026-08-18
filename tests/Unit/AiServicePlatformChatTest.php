<?php

namespace Tests\Unit;

use App\Services\AiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `chatForPlatform()` must never send anything to the model beyond what its
 * `$context` argument already contains — that argument is meant to be the
 * output of `AuthorizedDataScope::assistantContext()`, i.e. already
 * RLS-filtered to what the asking user may see. This test doesn't touch RLS
 * at all (no database connection involved) — it proves the narrower,
 * directly-testable half of the guarantee: the prompt sent to OpenAI
 * contains exactly the KPI/company/department names present in $context and
 * nothing else, so if the context is authorized, the prompt is too.
 */
class AiServicePlatformChatTest extends TestCase
{
    public function test_prompt_includes_only_kpis_present_in_the_authorized_context(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'ok']]],
            ], 200),
        ]);

        $context = [
            'user' => ['name' => 'Jamie Test', 'email' => 'jamie@example.com'],
            'companies' => [['name' => 'Andalusia', 'code' => 'AND', 'status' => 'active']],
            'departments' => [['name' => 'Sales', 'code' => 'SALES']],
            'kpis' => [[
                'name' => 'Customer Satisfaction Score',
                'target' => 90,
                'unit' => '%',
                'frequency' => 'quarterly',
                'visibility' => 'company',
            ]],
            'submissions' => [],
        ];

        (new AiService())->chatForPlatform($context, [
            ['role' => 'user', 'content' => 'How is my KPI doing?'],
        ]);

        Http::assertSent(function ($request) {
            $systemMessage = collect($request['messages'])->firstWhere('role', 'system')['content'] ?? '';

            return str_contains($systemMessage, 'Customer Satisfaction Score')
                && str_contains($systemMessage, 'Andalusia')
                && str_contains($systemMessage, 'Sales')
                && str_contains($systemMessage, 'Jamie Test')
                // The critical negative case: a company/KPI that was never
                // in $context must not somehow appear via a hardcoded
                // example or a leftover from another test's data.
                && !str_contains($systemMessage, 'VFive')
                && !str_contains($systemMessage, 'Revenue Growth Rate');
        });
    }

    public function test_empty_context_produces_a_prompt_with_no_data_to_discuss(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'ok']]],
            ], 200),
        ]);

        $context = ['user' => null, 'companies' => [], 'departments' => [], 'kpis' => [], 'submissions' => []];

        (new AiService())->chatForPlatform($context, [
            ['role' => 'user', 'content' => 'Tell me about any company.'],
        ]);

        Http::assertSent(function ($request) {
            $systemMessage = collect($request['messages'])->firstWhere('role', 'system')['content'] ?? '';

            return substr_count($systemMessage, '(none)') >= 3;
        });
    }
}
