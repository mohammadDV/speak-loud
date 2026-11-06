<?php

use App\Jobs\SendTelegramMessage;
use App\Services\Telegram\TelegramClient;
use App\Support\TelegramNotifier;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

function enableTelegramConfig(): void
{
    config([
        'services.telegram.enabled' => true,
        'services.telegram.bot_token' => 'test-bot-token',
        'services.telegram.notif_chat_id' => '@speakloudmate_notif_notification',
        'services.telegram.error_chat_id' => '@speakloudmate_error_notification',
        'services.telegram.queue' => 'low',
    ]);
}

test('telegram notify does nothing when disabled', function () {
    Bus::fake();

    config([
        'services.telegram.enabled' => false,
        'services.telegram.bot_token' => 'test-bot-token',
        'services.telegram.notif_chat_id' => '@notif',
    ]);

    TelegramNotifier::notify('Hello');

    Bus::assertNotDispatched(SendTelegramMessage::class);
});

test('telegram notify does nothing when token is missing', function () {
    Bus::fake();

    config([
        'services.telegram.enabled' => true,
        'services.telegram.bot_token' => '',
        'services.telegram.notif_chat_id' => '@notif',
    ]);

    TelegramNotifier::notify('Hello');

    Bus::assertNotDispatched(SendTelegramMessage::class);
});

test('telegram notify dispatches job to the notif chat on the configured queue', function () {
    Bus::fake();
    enableTelegramConfig();

    TelegramNotifier::notify('User registered');

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job): bool {
        return $job->chatId === '@speakloudmate_notif_notification'
            && $job->text === 'User registered'
            && $job->queue === 'low';
    });
});

test('telegram error dispatches job to the error chat', function () {
    Bus::fake();
    enableTelegramConfig();

    TelegramNotifier::error('Something broke');

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job): bool {
        return $job->chatId === '@speakloudmate_error_notification'
            && $job->text === 'Something broke';
    });
});

test('telegram notifier truncates long messages', function () {
    Bus::fake();
    enableTelegramConfig();

    TelegramNotifier::notify(str_repeat('a', 5000));

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job): bool {
        return mb_strlen($job->text) === 4096
            && str_ends_with($job->text, '…');
    });
});

test('telegram notify does nothing when chat id is missing', function () {
    Bus::fake();

    config([
        'services.telegram.enabled' => true,
        'services.telegram.bot_token' => 'test-bot-token',
        'services.telegram.notif_chat_id' => '',
        'services.telegram.error_chat_id' => '@error',
    ]);

    TelegramNotifier::notify('Hello');

    Bus::assertNotDispatched(SendTelegramMessage::class);
});

test('send telegram message job posts to the telegram api', function () {
    enableTelegramConfig();

    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true]),
    ]);

    $job = new SendTelegramMessage('@speakloudmate_notif_notification', 'Hello from SpeakLoudMate');
    $job->handle(app(TelegramClient::class));

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
            && $request['chat_id'] === '@speakloudmate_notif_notification'
            && $request['text'] === 'Hello from SpeakLoudMate';
    });
});

test('send telegram message job fails when telegram returns ok false', function () {
    enableTelegramConfig();

    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'chat not found']),
    ]);

    $job = new SendTelegramMessage('@bad-chat', 'Hello');

    expect(fn () => $job->handle(app(TelegramClient::class)))
        ->toThrow(RuntimeException::class, 'Telegram API rejected the message: chat not found');
});
