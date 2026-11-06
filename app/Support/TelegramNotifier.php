<?php

namespace App\Support;

use App\Jobs\SendTelegramMessage;
use Throwable;

class TelegramNotifier
{
    private const int MaxMessageLength = 4096;

    public static function notify(string $text): void
    {
        self::dispatch(config('services.telegram.notif_chat_id'), $text);
    }

    public static function error(string $text): void
    {
        self::dispatch(config('services.telegram.error_chat_id'), $text);
    }

    public static function formatException(Throwable $e): string
    {
        $lines = [
            'Exception: '.class_basename($e),
            'Message: '.$e->getMessage(),
            'File: '.$e->getFile().':'.$e->getLine(),
        ];

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            $lines[] = 'Context: console/queue';
        } else {
            $request = request();

            if ($request->method() !== '' && filled($request->fullUrl())) {
                $lines[] = 'Request: '.$request->method().' '.$request->fullUrl();
            }

            $user = $request->user();

            if ($user !== null) {
                $lines[] = 'User: #'.$user->getAuthIdentifier().' '.$user->email;
            }
        }

        return implode("\n", $lines);
    }

    private static function dispatch(mixed $chatId, string $text): void
    {
        if (! filter_var(config('services.telegram.enabled'), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $token = config('services.telegram.bot_token');

        if (! is_string($token) || $token === '') {
            return;
        }

        if (! is_string($chatId) || $chatId === '') {
            return;
        }

        $text = self::truncate(trim($text));

        if ($text === '') {
            return;
        }

        SendTelegramMessage::dispatch($chatId, $text);
    }

    private static function truncate(string $text): string
    {
        if (mb_strlen($text) <= self::MaxMessageLength) {
            return $text;
        }

        return mb_substr($text, 0, self::MaxMessageLength - 1).'…';
    }
}
