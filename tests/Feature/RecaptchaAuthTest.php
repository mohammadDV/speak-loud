<?php

use App\Models\User;
use App\Support\Recaptcha;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Livewire\Volt\Volt;

beforeEach(function () {
    config([
        'services.recaptcha.enabled' => true,
        'services.recaptcha.site_key' => 'test-site-key',
        'services.recaptcha.secret_key' => 'test-secret-key',
        'services.recaptcha.score_threshold' => 0.5,
    ]);
});

test('login rejects invalid recaptcha tokens', function () {
    User::factory()->create([
        'email' => 'captcha@example.com',
        'password' => Hash::make('Password1!'),
    ]);

    Http::fake([
        Recaptcha::VerifyUrl => Http::response([
            'success' => false,
            'score' => 0.1,
            'action' => 'login',
            'hostname' => 'localhost',
        ]),
    ]);

    Volt::test('auth.login')
        ->set('email', 'captcha@example.com')
        ->set('password', 'Password1!')
        ->set('recaptchaToken', 'bad-token')
        ->call('login')
        ->assertHasErrors('recaptchaToken');

    $this->assertGuest();
});

test('login accepts valid recaptcha tokens', function () {
    User::factory()->create([
        'email' => 'ok-captcha@example.com',
        'password' => Hash::make('Password1!'),
    ]);

    Http::fake([
        Recaptcha::VerifyUrl => Http::response([
            'success' => true,
            'score' => 0.9,
            'action' => 'login',
            'hostname' => 'localhost',
        ]),
    ]);

    Volt::test('auth.login')
        ->set('email', 'ok-captcha@example.com')
        ->set('password', 'Password1!')
        ->set('recaptchaToken', 'good-token')
        ->call('login')
        ->assertHasNoErrors()
        ->assertSet('recaptchaToken', '')
        ->assertRedirect(route('discover'));

    $this->assertAuthenticated();
});

test('register rejects invalid recaptcha tokens', function () {
    Http::fake([
        Recaptcha::VerifyUrl => Http::response([
            'success' => true,
            'score' => 0.1,
            'action' => 'register',
            'hostname' => 'localhost',
        ]),
    ]);

    Volt::test('auth.register')
        ->set('username', 'captchauser')
        ->set('email', 'new-captcha@example.com')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!')
        ->set('accepted_terms', true)
        ->set('recaptchaToken', 'low-score-token')
        ->call('register')
        ->assertHasErrors('recaptchaToken');

    $this->assertGuest();
    expect(User::query()->where('email', 'new-captcha@example.com')->exists())->toBeFalse();
});

test('forgot password rejects invalid recaptcha tokens', function () {
    Http::fake([
        Recaptcha::VerifyUrl => Http::response([
            'success' => true,
            'score' => 0.9,
            'action' => 'login',
            'hostname' => 'localhost',
        ]),
    ]);

    Password::shouldReceive('sendResetLink')->never();

    Volt::test('auth.forgot-password')
        ->set('email', 'reset@example.com')
        ->set('recaptchaToken', 'wrong-action-token')
        ->call('sendResetLink')
        ->assertHasErrors('recaptchaToken');
});
