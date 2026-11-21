<?php

use App\Support\Recaptcha;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'services.recaptcha.enabled' => true,
        'services.recaptcha.site_key' => 'test-site-key',
        'services.recaptcha.secret_key' => 'test-secret-key',
        'services.recaptcha.score_threshold' => 0.5,
    ]);
});

test('verify skips when recaptcha is disabled', function () {
    config(['services.recaptcha.enabled' => false]);

    Http::fake();

    expect(Recaptcha::verify(null, 'login'))->toBeTrue();

    Http::assertNothingSent();
});

test('enabled requires the feature flag plus both keys', function () {
    config([
        'services.recaptcha.enabled' => true,
        'services.recaptcha.site_key' => 'test-site-key',
        'services.recaptcha.secret_key' => 'test-secret-key',
    ]);

    expect(Recaptcha::enabled())->toBeTrue();

    config(['services.recaptcha.site_key' => '']);

    expect(Recaptcha::enabled())->toBeFalse();
});

test('verify rejects empty tokens when enabled', function () {
    Http::fake();

    expect(Recaptcha::verify('', 'login'))->toBeFalse()
        ->and(Recaptcha::verify(null, 'login'))->toBeFalse();

    Http::assertNothingSent();
});

test('verify accepts a successful high-score response for the expected action', function () {
    Http::fake([
        Recaptcha::VerifyUrl => Http::response([
            'success' => true,
            'score' => 0.9,
            'action' => 'login',
            'hostname' => 'localhost',
        ]),
    ]);

    expect(Recaptcha::verify('token-abc', 'login'))->toBeTrue();

    Http::assertSent(fn ($request) => $request->url() === Recaptcha::VerifyUrl
        && $request['response'] === 'token-abc'
        && $request['secret'] === 'test-secret-key');
});

test('verify rejects low scores wrong actions failed responses and hostnames', function (array $payload) {
    Http::fake([
        Recaptcha::VerifyUrl => Http::response($payload),
    ]);

    expect(Recaptcha::verify('token-abc', 'login'))->toBeFalse();
})->with([
    'failed' => [['success' => false, 'score' => 0.9, 'action' => 'login', 'hostname' => 'localhost']],
    'low score' => [['success' => true, 'score' => 0.2, 'action' => 'login', 'hostname' => 'localhost']],
    'wrong action' => [['success' => true, 'score' => 0.9, 'action' => 'register', 'hostname' => 'localhost']],
    'wrong host' => [['success' => true, 'score' => 0.9, 'action' => 'login', 'hostname' => 'evil.example']],
]);

test('score threshold is clamped between zero and one', function () {
    config(['services.recaptcha.score_threshold' => 5]);

    expect(Recaptcha::scoreThreshold())->toBe(1.0);

    config(['services.recaptcha.score_threshold' => -1]);

    expect(Recaptcha::scoreThreshold())->toBe(0.0);
});
