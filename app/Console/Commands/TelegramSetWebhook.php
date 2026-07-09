<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {url? : Full https URL to the webhook route, defaults to APP_URL/api/telegram/webhook}';

    protected $description = 'Registers the Telegram bot webhook with this app\'s /api/telegram/webhook endpoint';

    public function handle(TelegramService $telegram): int
    {
        $url = $this->argument('url') ?: rtrim(env('APP_URL'), '/') . '/api/telegram/webhook';
        $secret = env('TELEGRAM_WEBHOOK_SECRET', '');

        if ($secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET is not set in .env');
            return self::FAILURE;
        }

        $this->info("Setting webhook to: {$url}");
        $result = $telegram->setWebhook($url, $secret);
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        if (!($result['ok'] ?? false)) {
            $this->error('setWebhook failed.');
            return self::FAILURE;
        }

        $info = $telegram->getWebhookInfo();
        $this->info('Current webhook info:');
        $this->line(json_encode($info, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
