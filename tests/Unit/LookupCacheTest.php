<?php

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Interest;
use App\Models\Language;
use App\Support\LookupCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    LookupCache::flush();
    Cache::flush();
});

it('caches active languages as attributes and hydrates models without incomplete classes', function () {
    Language::create([
        'code' => 'en',
        'name_en' => 'English',
        'name_native' => 'English',
        'is_active' => true,
    ]);

    $first = LookupCache::activeLanguages();
    $second = LookupCache::activeLanguages();

    expect($first)->toHaveCount(1)
        ->and($first->first())->toBeInstanceOf(Language::class)
        ->and($second)->toHaveCount(1)
        ->and($second->first())->toBeInstanceOf(Language::class)
        ->and($second->first()->code)->toBe('en');
});

it('flushes lookup cache when a language is saved', function () {
    Language::create([
        'code' => 'en',
        'name_en' => 'English',
        'name_native' => 'English',
        'is_active' => true,
    ]);

    LookupCache::activeLanguages();

    Language::create([
        'code' => 'fa',
        'name_en' => 'Persian',
        'name_native' => 'فارسی',
        'is_active' => true,
    ]);

    expect(LookupCache::activeLanguages())->toHaveCount(2);
});

it('caches active faqs with categories', function () {
    $category = FaqCategory::create([
        'name' => 'General',
        'slug' => 'general',
        'sort_order' => 1,
    ]);

    Faq::create([
        'category_id' => $category->id,
        'question' => 'How does it work?',
        'answer' => 'You claim a slot.',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Faq::create([
        'category_id' => $category->id,
        'question' => 'Hidden',
        'answer' => 'Nope',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    expect(LookupCache::activeFaqs())->toHaveCount(1)
        ->and(LookupCache::activeFaqs()->first()->question)->toBe('How does it work?');
});

it('caches interests list', function () {
    Interest::create(['slug' => 'music', 'name_en' => 'Music']);
    Interest::create(['slug' => 'sports', 'name_en' => 'Sports']);

    expect(LookupCache::interests())->toHaveCount(2)
        ->and(LookupCache::interests()->pluck('slug')->all())->toContain('music', 'sports');
});
