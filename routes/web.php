<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', fn() => view('pages.home'))->name('home');
Route::get('/about', fn() => view('pages.about'))->name('about');
Route::get('/contact', fn() => view('pages.contact'))->name('contact');
Route::get('/support', fn() => view('pages.support'))->name('support');
Volt::route('/blog', 'blog.index')->name('blog.index');
Volt::route('/blog/{slug}', 'blog.show')->name('blog.show');

Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
    Volt::route('/register', 'auth.register')->name('register');
});

Route::middleware('auth')->group(function () {
    Volt::route('/discover', 'discover.index')->name('discover');
    Volt::route('/search', 'discover.search')->name('search');
    Volt::route('/schedule', 'schedule.index')->name('schedule');
    Volt::route('/messages', 'messages.index')->name('messages');
    Volt::route('/messages/{id}', 'messages.show')->name('messages.show');
    Volt::route('/profile', 'profile.overview')->name('profile');
    Volt::route('/profile/edit', 'profile.edit')->name('profile.edit');
    Volt::route('/claims', 'claims.index')->name('claims');
    Route::post('/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('logout');
});
