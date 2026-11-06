<?php

namespace App\Jobs;

use App\Services\Telegram\TelegramClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $chatId,
        public string $text,
    ) {
        $queue = config('services.telegram.queue', 'low');

        $this->onQueue(is_string($queue) && $queue !== '' ? $queue : 'low');
        $this->afterCommit();
    }

    public function handle(TelegramClient $client): void
    {
        $client->sendMessage($this->chatId, $this->text);
    }
}
