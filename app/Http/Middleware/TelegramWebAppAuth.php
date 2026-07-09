<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TelegramWebAppAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $initData = $request->header('X-Telegram-Init-Data', '');

        $telegramUser = self::verify($initData, env('TELEGRAM_BOT_TOKEN', ''));

        if (!$telegramUser) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired Telegram session.',
            ], 401);
        }

        $request->attributes->set('telegram_user', $telegramUser);

        return $next($request);
    }

    /**
     * Verifies a Telegram Mini App initData string against the bot token.
     * https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
     *
     * @return array{id:int,username:?string,first_name:?string}|null
     */
    public static function verify(string $initData, string $botToken): ?array
    {
        if ($initData === '' || $botToken === '') {
            return null;
        }

        parse_str($initData, $data);

        if (empty($data['hash']) || empty($data['user']) || empty($data['auth_date'])) {
            return null;
        }

        $hash = $data['hash'];
        unset($data['hash']);

        ksort($data);

        $dataCheckString = collect($data)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $expectedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($expectedHash, $hash)) {
            return null;
        }

        if ((now()->timestamp - (int) $data['auth_date']) > 86400) {
            return null;
        }

        $user = json_decode($data['user'], true);

        if (empty($user['id'])) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'username' => $user['username'] ?? null,
            'first_name' => $user['first_name'] ?? null,
        ];
    }
}
