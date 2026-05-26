<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    \App\Support\Seo::share([
        'seoTitle'       => 'Practice languages with real people',
        'seoDescription' => 'SpeakLoud connects language learners with partners for scheduled practice. Publish slots, accept claims, and talk with real people.',
        'seoUrl'         => route('home'),
    ]);

    return view('pages.home');
})->name('home');
Route::get('/about', fn() => view('pages.about'))->name('about');
Route::get('/contact', fn() => view('pages.contact'))->name('contact');
Route::get('/support', fn() => view('pages.support'))->name('support');
Route::get('/terms', fn() => view('pages.terms'))->name('terms');
Volt::route('/blog', 'blog.index')->name('blog.index');
Volt::route('/blog/{slug}', 'blog.show')->name('blog.show');
Volt::route('/faq', 'faq.index')->name('faq.index');
Volt::route('/discover', 'discover.index')->name('discover');
Volt::route('/u/{profileSlug}', 'users.show')->name('users.show');

Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
    Volt::route('/register', 'auth.register')->name('register');
});

Route::middleware('auth')->group(function () {
    Volt::route('/schedule', 'schedule.index')->name('schedule');
    Volt::route('/schedules/{schedule}', 'schedule.show')->name('schedules.show');
    Volt::route('/messages', 'messages.index')->name('messages');
    Volt::route('/messages/{id}', 'messages.show')->name('messages.show');
    Volt::route('/profile', 'profile.overview')->name('profile');
    Volt::route('/profile/edit', 'profile.edit')->name('profile.edit');
    Volt::route('/claims', 'claims.index')->name('claims');
    Route::post('/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('logout');
});
