<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramClient
{
    private const int TimeoutSeconds = 10;

    /**
     * @throws RequestException
     * @throws RuntimeException
     */
    public function sendMessage(string $chatId, string $text): void
    {
        $token = config('services.telegram.bot_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(self::TimeoutSeconds)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ])
            ->throw();

        if ($response->json('ok') !== true) {
            $description = $response->json('description');

            throw new RuntimeException(
                'Telegram API rejected the message'.(is_string($description) && $description !== '' ? ": {$description}" : '.')
            );
        }
    }
}
