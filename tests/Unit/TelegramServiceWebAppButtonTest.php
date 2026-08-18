<?php

namespace Tests\Unit;

use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression test for a real, pre-existing bug found while verifying the
 * Telegram digest against the real Bot API: `webAppButton()` returned an
 * array one nesting level short of what Telegram's `inline_keyboard` field
 * requires (an array of ROWS, each row an array of buttons) — every caller
 * passed the result straight through to `sendMessage()`, and Telegram's own
 * API rejected the request with "expected an Array of InlineKeyboardButton."
 * This predates this session and was only caught because a real send was
 * actually attempted, not mocked.
 */
class TelegramServiceWebAppButtonTest extends TestCase
{
    public function test_web_app_button_produces_a_valid_single_row_single_button_keyboard(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $service = new TelegramService();
        $keyboard = $service->webAppButton('Open', 'https://example.com/app');

        // inline_keyboard: Array of Array of InlineKeyboardButton — one row,
        // containing one button.
        $this->assertCount(1, $keyboard);
        $this->assertCount(1, $keyboard[0]);
        $this->assertSame('Open', $keyboard[0][0]['text']);
        $this->assertSame('https://example.com/app', $keyboard[0][0]['web_app']['url']);

        $service->sendMessage(123, 'hi', $keyboard);

        Http::assertSent(function ($request) {
            $markup = json_decode($request['reply_markup'], true);

            return isset($markup['inline_keyboard'][0][0]['text']);
        });
    }
}
