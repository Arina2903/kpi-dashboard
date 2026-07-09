<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $token;

    public function __construct()
    {
        $this->token = env('TELEGRAM_BOT_TOKEN', '');
    }

    private function request()
    {
        return Http::timeout(15)->connectTimeout(5);
    }

    private function endpoint(string $method): string
    {
        return "https://api.telegram.org/bot{$this->token}/{$method}";
    }

    public function getMe(): array
    {
        return $this->call('getMe');
    }

    public function setWebhook(string $url, string $secretToken): array
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secretToken,
            'allowed_updates' => ['message'],
        ]);
    }

    public function getWebhookInfo(): array
    {
        return $this->call('getWebhookInfo');
    }

    public function sendMessage(int $chatId, string $text, ?array $inlineKeyboard = null, string $parseMode = 'HTML'): array
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($inlineKeyboard) {
            $payload['reply_markup'] = json_encode(['inline_keyboard' => $inlineKeyboard]);
        }

        return $this->call('sendMessage', $payload);
    }

    public function webAppButton(string $label, string $url): array
    {
        return [[
            'text' => $label,
            'web_app' => ['url' => $url],
        ]];
    }

    private function call(string $method, array $payload = []): array
    {
        try {
            $response = $this->request()->post($this->endpoint($method), $payload);

            if (!$response->successful()) {
                Log::error("Telegram API {$method} failed", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['ok' => false, 'error' => $response->body()];
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error("Telegram API {$method} threw", ['message' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
