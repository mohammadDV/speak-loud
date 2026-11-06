<?php

use App\Jobs\SendTelegramMessage;
use App\Services\Telegram\TelegramClient;
use App\Support\TelegramExceptionFilter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function enableTelegramErrorConfig(): void
{
    config([
        'services.telegram.enabled' => true,
        'services.telegram.bot_token' => 'test-bot-token',
        'services.telegram.notif_chat_id' => '@notif',
        'services.telegram.error_chat_id' => '@error',
        'services.telegram.queue' => 'low',
    ]);
}

test('noisy exceptions are skipped by the telegram filter', function (Throwable $exception) {
    expect(TelegramExceptionFilter::shouldSkip($exception))->toBeTrue();
})->with([
    'validation' => fn () => ValidationException::withMessages(['email' => 'invalid']),
    'authentication' => fn () => new AuthenticationException,
    'authorization' => fn () => new AuthorizationException,
    'not found http' => fn () => new NotFoundHttpException,
    'model not found' => fn () => new ModelNotFoundException,
    'csrf' => fn () => new TokenMismatchException,
    'throttle' => fn () => new ThrottleRequestsException('Too many attempts.'),
    'http 404' => fn () => new HttpException(404),
    'http 403' => fn () => new HttpException(403),
]);

test('server errors are not skipped by the telegram filter', function () {
    expect(TelegramExceptionFilter::shouldSkip(new RuntimeException('boom')))->toBeFalse()
        ->and(TelegramExceptionFilter::shouldSkip(new HttpException(500, 'fail')))->toBeFalse();
});

test('reporting a runtime exception queues a telegram error job', function () {
    Bus::fake();
    enableTelegramErrorConfig();

    report(new RuntimeException('Unexpected failure'));

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job): bool {
        return $job->chatId === '@error'
            && str_contains($job->text, 'RuntimeException')
            && str_contains($job->text, 'Unexpected failure');
    });
});

test('reporting a validation exception does not queue a telegram error job', function () {
    Bus::fake();
    enableTelegramErrorConfig();

    report(ValidationException::withMessages(['email' => 'invalid']));

    Bus::assertNotDispatched(SendTelegramMessage::class);
});

test('telegram job failures do not queue another telegram error job', function () {
    Bus::fake();
    enableTelegramErrorConfig();

    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden'], 200),
    ]);

    try {
        app(TelegramClient::class)->sendMessage('@error', 'will fail');
    } catch (Throwable $e) {
        report($e);
    }

    Bus::assertNotDispatched(SendTelegramMessage::class);
});
