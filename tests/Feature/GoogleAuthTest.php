<?php

use App\Actions\HandleGoogleCallback;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\AuthSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Volt\Volt;

function pendingGoogleSession(
    string $providerId = 'google-sub-new',
    string $email = 'newgoogle@example.com',
    ?string $name = 'New Google',
    ?int $expiresAt = null,
): array {
    return [
        HandleGoogleCallback::PENDING_SESSION_KEY => [
            'provider_id' => $providerId,
            'email' => $email,
            'name' => $name,
            'expires_at' => $expiresAt ?? now()->addMinutes(15)->timestamp,
        ],
    ];
}

function mockGoogleUser(
    string $id = 'google-sub-123',
    string $email = 'google@example.com',
    string $name = 'Google User',
    bool|string|int $emailVerified = true,
): SocialiteUser {
    $user = (new SocialiteUser)->map([
        'id' => $id,
        'nickname' => null,
        'name' => $name,
        'email' => $email,
        'avatar' => null,
    ]);

    $user->user = [
        'email_verified' => $emailVerified,
    ];

    return $user;
}

function mockSocialiteGoogle(SocialiteUser $googleUser): void
{
    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);
}

test('existing google social account logs the user in', function () {
    $user = User::factory()->withGoogle('google-sub-123')->create([
        'email' => 'linked@example.com',
    ]);
    UserProfile::create([
        'user_id' => $user->id,
        'username' => 'linkeduser',
        'profile_slug' => 'linkeduser',
        'display_name' => 'Linked User',
    ]);

    mockSocialiteGoogle(mockGoogleUser(
        id: 'google-sub-123',
        email: 'linked@example.com',
    ));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('discover'));

    $this->assertAuthenticatedAs($user);
});

test('google oauth auto links verified local account and keeps password', function () {
    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'password' => 'Password1!',
    ]);
    UserProfile::create([
        'user_id' => $user->id,
        'username' => 'existinguser',
        'profile_slug' => 'existinguser',
        'display_name' => 'Existing User',
    ]);

    mockSocialiteGoogle(mockGoogleUser(
        id: 'google-sub-456',
        email: 'existing@example.com',
    ));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('discover'));

    $this->assertAuthenticatedAs($user);

    $user->refresh();

    expect($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->hasPassword())->toBeTrue()
        ->and(Hash::check('Password1!', $user->password))->toBeTrue()
        ->and($user->hasGoogleAccount())->toBeTrue()
        ->and($user->socialAccounts()->where('provider_id', 'google-sub-456')->exists())->toBeTrue();
});

test('google oauth reclaiming unverified squatted account clears password and sessions', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'squatted@example.com',
        'password' => 'AttackerPass1!',
        'remember_token' => 'old-remember-token',
        'session_version' => 0,
    ]);
    UserProfile::create([
        'user_id' => $user->id,
        'username' => 'squatteduser',
        'profile_slug' => 'squatteduser',
        'display_name' => 'Squatted User',
        'bio' => 'Attacker bio',
        'profile_image_path' => 'profiles/attacker.jpg',
    ]);

    DB::table('sessions')->insert([
        'id' => 'attacker-session-id',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => 'payload',
        'last_activity' => time(),
    ]);

    mockSocialiteGoogle(mockGoogleUser(
        id: 'google-sub-reclaim',
        email: 'squatted@example.com',
        name: 'Real Owner',
    ));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('discover'))
        ->assertSessionHas('oauth_status');

    $this->assertAuthenticatedAs($user);

    $user->refresh();
    $profile = $user->profile()->first();

    expect($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->hasPassword())->toBeFalse()
        ->and($user->remember_token)->not->toBe('old-remember-token')
        ->and($user->session_version)->toBe(1)
        ->and($user->hasGoogleAccount())->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $user->id)->where('id', 'attacker-session-id')->exists())->toBeFalse()
        ->and($profile?->display_name)->toBe('Real Owner')
        ->and($profile?->bio)->toBeNull()
        ->and($profile?->profile_image_path)->toBeNull()
        ->and($profile?->username)->toBe('squatteduser');

    auth()->logout();

    Volt::test('auth.login')
        ->set('email', 'squatted@example.com')
        ->set('password', 'AttackerPass1!')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('new google user is redirected to complete profile', function () {
    mockSocialiteGoogle(mockGoogleUser(
        id: 'google-sub-new',
        email: 'newgoogle@example.com',
        name: 'New Google',
    ));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('auth.google.complete'));

    $this->assertGuest();

    expect(session(HandleGoogleCallback::PENDING_SESSION_KEY))->toMatchArray([
        'provider_id' => 'google-sub-new',
        'email' => 'newgoogle@example.com',
        'name' => 'New Google',
    ])->and(session(HandleGoogleCallback::PENDING_SESSION_KEY)['expires_at'])->toBeGreaterThan(now()->timestamp);
});

test('complete google profile creates verified user without password', function () {
    session(pendingGoogleSession());

    Volt::test('auth.complete-google-profile')
        ->set('username', 'newgoogleuser')
        ->set('accepted_terms', true)
        ->call('complete')
        ->assertHasNoErrors()
        ->assertRedirect(route('discover'));

    $user = User::query()->where('email', 'newgoogle@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasPassword())->toBeFalse()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->hasGoogleAccount())->toBeTrue()
        ->and($user->profile?->username)->toBe('newgoogleuser')
        ->and($user->terms_accepted_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('unverified google email is rejected', function () {
    mockSocialiteGoogle(mockGoogleUser(emailVerified: false));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('oauth_error');

    $this->assertGuest();
});

test('banned google user cannot log in', function () {
    $user = User::factory()->banned()->withGoogle('google-banned')->create([
        'email' => 'banned-google@example.com',
    ]);

    mockSocialiteGoogle(mockGoogleUser(
        id: 'google-banned',
        email: 'banned-google@example.com',
    ));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('oauth_error');

    $this->assertGuest();
    expect($user->fresh()->status)->toBe('banned');
});

test('inactive google user cannot log in', function () {
    User::factory()->inactive()->withGoogle('google-inactive')->create([
        'email' => 'inactive-google@example.com',
    ]);

    mockSocialiteGoogle(mockGoogleUser(
        id: 'google-inactive',
        email: 'inactive-google@example.com',
    ));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('oauth_error');

    $this->assertGuest();
});

test('dual auth user can still log in with password', function () {
    User::factory()->withGoogle('google-dual')->create([
        'email' => 'dual@example.com',
        'password' => 'Password1!',
    ]);

    Volt::test('auth.login')
        ->set('email', 'dual@example.com')
        ->set('password', 'Password1!')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('discover'));

    $this->assertAuthenticated();
});

test('google-only user cannot authenticate with password form', function () {
    User::factory()->withoutPassword()->withGoogle('google-only')->create([
        'email' => 'googleonly@example.com',
    ]);

    Volt::test('auth.login')
        ->set('email', 'googleonly@example.com')
        ->set('password', 'anypassword')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('google-only user can set a password on security page', function () {
    $user = User::factory()->withoutPassword()->withGoogle('google-set-pass')->create([
        'email' => 'setpass@example.com',
    ]);

    Volt::actingAs($user)->test('profile.security')
        ->set('email_confirmation', 'setpass@example.com')
        ->set('password', 'Newpassword1!')
        ->set('password_confirmation', 'Newpassword1!')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSet('status', 'password-set');

    expect($user->fresh()->hasPassword())->toBeTrue()
        ->and(Hash::check('Newpassword1!', $user->fresh()->password))->toBeTrue();

    auth()->logout();

    Volt::test('auth.login')
        ->set('email', 'setpass@example.com')
        ->set('password', 'Newpassword1!')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('discover'));

    $this->assertAuthenticated();
});

test('change password still requires current password when one exists', function () {
    $user = User::factory()->withGoogle()->create([
        'password' => 'Oldpassword1!',
    ]);

    Volt::actingAs($user)->test('profile.security')
        ->set('current_password', 'wrongpassword')
        ->set('password', 'Newpassword1!')
        ->set('password_confirmation', 'Newpassword1!')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('Oldpassword1!', $user->fresh()->password))->toBeTrue();
});

test('complete google profile requires terms and unique username', function () {
    session(pendingGoogleSession('google-sub-validate', 'validate@example.com', 'Validate User'));

    Volt::test('auth.complete-google-profile')
        ->set('username', 'validuser')
        ->set('accepted_terms', false)
        ->call('complete')
        ->assertHasErrors(['accepted_terms']);

    $existing = User::factory()->create();
    UserProfile::create([
        'user_id' => $existing->id,
        'username' => 'takenname',
        'profile_slug' => 'takenname',
        'display_name' => 'Taken',
    ]);

    Volt::test('auth.complete-google-profile')
        ->set('username', 'takenname')
        ->set('accepted_terms', true)
        ->call('complete')
        ->assertHasErrors(['username']);

    Volt::test('auth.complete-google-profile')
        ->set('username', 'admin')
        ->set('accepted_terms', true)
        ->call('complete')
        ->assertHasErrors(['username']);
});

test('login and register pages include google oauth button', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Continue with Google')
        ->assertSee(route('auth.google.redirect'), false);

    $this->get(route('register'))
        ->assertSuccessful()
        ->assertSee('Continue with Google');
});

test('password reset works for google-only users', function () {
    $user = User::factory()->withoutPassword()->withGoogle()->create([
        'email' => 'resetgoogle@example.com',
    ]);

    $token = Password::createToken($user);

    Volt::test('auth.reset-password', ['token' => $token])
        ->set('email', 'resetgoogle@example.com')
        ->set('password', 'Newpassword1!')
        ->set('password_confirmation', 'Newpassword1!')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect($user->fresh()->hasPassword())->toBeTrue()
        ->and(Hash::check('Newpassword1!', $user->fresh()->password))->toBeTrue();
});

test('string false google email_verified is rejected', function () {
    mockSocialiteGoogle(mockGoogleUser(emailVerified: 'false'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('oauth_error');

    $this->assertGuest();
});

test('soft deleted user email cannot complete google registration', function () {
    $user = User::factory()->create(['email' => 'deleted@example.com']);
    $user->delete();

    session(pendingGoogleSession('google-sub-deleted', 'deleted@example.com', 'Deleted User'));

    Volt::test('auth.complete-google-profile')
        ->set('username', 'deleteduser')
        ->set('accepted_terms', true)
        ->call('complete')
        ->assertHasErrors(['username']);

    expect(User::query()->where('email', 'deleted@example.com')->exists())->toBeFalse();
});

test('soft deleted user email is rejected on google callback', function () {
    $user = User::factory()->create(['email' => 'gone@example.com']);
    $user->delete();

    mockSocialiteGoogle(mockGoogleUser(
        id: 'google-sub-gone',
        email: 'gone@example.com',
    ));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('oauth_error');

    $this->assertGuest();
});

test('mismatched google provider id on existing linked account is rejected', function () {
    $user = User::factory()->withGoogle('google-original-sub')->create([
        'email' => 'linked-mismatch@example.com',
        'password' => 'Password1!',
    ]);

    mockSocialiteGoogle(mockGoogleUser(
        id: 'google-different-sub',
        email: 'linked-mismatch@example.com',
    ));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('oauth_error');

    $this->assertGuest();
    expect($user->fresh()->socialAccounts()->where('provider_id', 'google-original-sub')->exists())->toBeTrue()
        ->and($user->fresh()->socialAccounts()->where('provider_id', 'google-different-sub')->exists())->toBeFalse();
});

test('long google display name is truncated on complete profile', function () {
    $longName = str_repeat('N', 150);

    session(pendingGoogleSession('google-sub-long-name', 'longname@example.com', $longName));

    Volt::test('auth.complete-google-profile')
        ->set('username', 'longnameuser')
        ->set('accepted_terms', true)
        ->call('complete')
        ->assertHasNoErrors()
        ->assertRedirect(route('discover'));

    $user = User::query()->where('email', 'longname@example.com')->first();

    expect($user)->not->toBeNull()
        ->and(mb_strlen($user->profile->display_name))->toBe(100)
        ->and($user->profile->display_name)->toBe(str_repeat('N', 100));
});

test('google callback regenerates session before storing pending registration', function () {
    $this->withSession(['marker' => 'before-oauth']);

    $previousSessionId = session()->getId();

    mockSocialiteGoogle(mockGoogleUser(
        id: 'google-sub-pending-regen',
        email: 'pendingregen@example.com',
        name: 'Pending Regen',
    ));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('auth.google.complete'));

    expect(session()->getId())->not->toBe($previousSessionId)
        ->and(session(HandleGoogleCallback::PENDING_SESSION_KEY))->toMatchArray([
            'provider_id' => 'google-sub-pending-regen',
            'email' => 'pendingregen@example.com',
            'name' => 'Pending Regen',
        ]);
});

test('set password invalidates other database sessions', function () {
    $user = User::factory()->withoutPassword()->withGoogle('google-set-sessions')->create([
        'email' => 'setsessions@example.com',
        'session_version' => 0,
    ]);

    DB::table('sessions')->insert([
        [
            'id' => 'other-device-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'other',
            'payload' => 'payload',
            'last_activity' => time(),
        ],
    ]);

    Volt::actingAs($user)->test('profile.security')
        ->set('email_confirmation', 'setsessions@example.com')
        ->set('password', 'Newpassword1!')
        ->set('password_confirmation', 'Newpassword1!')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSet('status', 'password-set');

    expect(DB::table('sessions')->where('id', 'other-device-session')->exists())->toBeFalse()
        ->and($user->fresh()->hasPassword())->toBeTrue()
        ->and($user->fresh()->session_version)->toBe(1)
        ->and(session(AuthSession::VERSION_KEY))->toBe(1);
});

test('expired google pending session cannot complete profile', function () {
    session(pendingGoogleSession(
        providerId: 'google-sub-expired',
        email: 'expired@example.com',
        name: 'Expired',
        expiresAt: now()->subMinute()->timestamp,
    ));

    Volt::test('auth.complete-google-profile')
        ->assertRedirect(route('login'));
});

test('set password requires matching email confirmation', function () {
    $user = User::factory()->withoutPassword()->withGoogle('google-email-confirm')->create([
        'email' => 'confirm@example.com',
    ]);

    Volt::actingAs($user)->test('profile.security')
        ->set('email_confirmation', 'wrong@example.com')
        ->set('password', 'Newpassword1!')
        ->set('password_confirmation', 'Newpassword1!')
        ->call('updatePassword')
        ->assertHasErrors(['email_confirmation']);

    expect($user->fresh()->hasPassword())->toBeFalse();
});

test('login normalizes email casing', function () {
    User::factory()->create([
        'email' => 'cased@example.com',
        'password' => 'Password1!',
    ]);

    Volt::test('auth.login')
        ->set('email', 'Cased@Example.com')
        ->set('password', 'Password1!')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('discover'));

    $this->assertAuthenticated();
});

test('stale session version logs the user out', function () {
    $user = User::factory()->create([
        'session_version' => 2,
    ]);

    $this->actingAs($user);
    session([AuthSession::VERSION_KEY => 1]);

    $this->get(route('discover'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
