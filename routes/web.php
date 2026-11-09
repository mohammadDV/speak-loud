<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Support\Seo;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    $howToSteps = [
        [
            'n' => '1',
            'title' => 'Create your profile',
            'body' => 'Sign up, pick a username for your public link, and verify your email so you can publish slots and claim practice time.',
            'label' => 'Create your profile',
            'src' => 'images/screenshots/create-your-profile.webp',
            'accent' => '#FF8C42',
        ],
        [
            'n' => '2',
            'title' => 'Publish practice hours',
            'body' => 'Open Schedule and add weekly or one-off slots for the languages you want to practice or help with.',
            'label' => 'Publish practice hours',
            'src' => 'images/screenshots/publish-practice-hours.webp',
            'accent' => '#E67A35',
        ],
        [
            'n' => '3',
            'title' => 'Claim or accept partners',
            'body' => 'Browse Discover to claim open slots, or review people who claim yours and accept the partners that fit.',
            'label' => 'Claim or accept partners',
            'src' => 'images/screenshots/claim-or-accept-partners.webp',
            'accent' => '#D35400',
        ],
        [
            'n' => '4',
            'title' => 'Talk and message',
            'body' => 'Use Messages to coordinate details, then hop on your call and keep the rhythm going next week.',
            'label' => 'Talk and message',
            'src' => 'images/screenshots/talk-and-message.webp',
            'accent' => '#FF8C42',
        ],
    ];

    Seo::share([
        'seoTitle' => 'Practice languages with real people',
        'seoDescription' => 'SpeakLoudMate connects language learners with partners for scheduled practice. Publish slots, accept claims, and talk with real people.',
        'seoUrl' => route('home'),
        'seoJsonLd' => [
            Seo::organizationSchema(),
            Seo::websiteSchema(),
            Seo::howToSchema(
                'Get from signup to your first practice session',
                'Four steps. Publish when you are free, match with a partner, and start talking.',
                array_map(fn (array $step): array => [
                    'name' => $step['title'],
                    'text' => $step['body'],
                    'image' => asset($step['src']),
                ], $howToSteps),
            ),
        ],
    ]);

    return view('pages.home', ['howToSteps' => $howToSteps]);
})->name('home');
Route::get('/about', fn () => view('pages.about'))->name('about');
Route::get('/contact', fn () => view('pages.contact'))->name('contact');
Route::get('/donate', fn () => view('pages.donate'))->name('donate');
Route::get('/support', fn () => view('pages.support'))->name('support');
Route::get('/terms', fn () => view('pages.terms'))->name('terms');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Volt::route('/blog', 'blog.index')->name('blog.index');
Volt::route('/blog/{slug}', 'blog.show')->name('blog.show');
Volt::route('/faq', 'faq.index')->name('faq.index');
Volt::route('/discover', 'discover.index')->name('discover');
Volt::route('/u/{profileSlug}', 'users.show')->name('users.show');

Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
    Volt::route('/register', 'auth.register')->name('register');
    Volt::route('/forgot-password', 'auth.forgot-password')->name('password.request');
    Volt::route('/reset-password/{token}', 'auth.reset-password')->name('password.reset');

    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware('throttle:10,1')
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:20,1')
        ->name('auth.google.callback');
    Volt::route('/auth/google/complete', 'auth.complete-google-profile')
        ->name('auth.google.complete');
});

Route::middleware(['auth', 'active'])->group(function () {
    Volt::route('/email/verify', 'auth.verify-email')->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('profile.edit')->with('verified', true);
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('verified')->group(function () {
        Volt::route('/profile', 'profile.overview')->name('profile');
        Volt::route('/profile/edit', 'profile.edit')->name('profile.edit');
        Volt::route('/profile/security', 'profile.security')->name('profile.security');
        Volt::route('/schedule', 'schedule.index')->name('schedule');
        Volt::route('/schedules/{schedule}', 'schedule.show')->name('schedules.show');
        Volt::route('/messages', 'messages.index')->name('messages');
        Volt::route('/messages/{id}', 'messages.show')->name('messages.show');
        Volt::route('/claims', 'claims.index')->name('claims');
        Volt::route('/tickets', 'tickets.index')->name('tickets.index');
        Volt::route('/tickets/{ticket}', 'tickets.show')->name('tickets.show');
    });
});
