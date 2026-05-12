<?php

namespace App\Logging;

use Illuminate\Support\Facades\Http;

class TelegramNotifier
{
    public static function reportException(\Throwable $e): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            return;
        }

        try {
            Http::timeout(5)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => self::formatMessage($e),
                    'parse_mode' => 'HTML',
                ]
            );
        } catch (\Throwable) {
            // A notification failure must never break the error response.
        }
    }

    private static function formatMessage(\Throwable $e): string
    {
        $app = htmlspecialchars(config('app.name'), ENT_QUOTES);
        $env = htmlspecialchars(config('app.env'), ENT_QUOTES);

        $lines = [
            "🚨 <b>500 Error</b> — {$app} [{$env}]",
            '<b>Time:</b> '.now()->format('Y-m-d H:i:s'),
            '<b>Class:</b> '.htmlspecialchars(get_class($e), ENT_QUOTES),
            '<b>Message:</b> '.htmlspecialchars($e->getMessage(), ENT_QUOTES),
            '<b>File:</b> '.htmlspecialchars($e->getFile().':'.$e->getLine(), ENT_QUOTES),
        ];

        try {
            $lines[] = '<b>URL:</b> '.htmlspecialchars(request()->fullUrl(), ENT_QUOTES);
        } catch (\Throwable) {
        }

        return implode("\n", $lines);
    }
}
