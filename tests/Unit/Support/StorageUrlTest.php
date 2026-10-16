<?php

use App\Support\StorageUrl;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('resolve returns null for empty paths', function () {
    expect(StorageUrl::resolve(null))->toBeNull()
        ->and(StorageUrl::resolve(''))->toBeNull();
});

test('resolve passes through absolute http urls', function () {
    expect(StorageUrl::resolve('https://cdn.example.com/cover.jpg'))
        ->toBe('https://cdn.example.com/cover.jpg');
});

test('resolve uses asset for bundled public image paths', function () {
    expect(StorageUrl::resolve('images/blog/welcome.svg'))
        ->toBe(asset('images/blog/welcome.svg'));
});

test('resolve uses the s3 disk for uploaded object keys', function () {
    Storage::fake('s3');

    $url = StorageUrl::resolve('blog/cover-images/photo.jpg');

    expect($url)->toContain('blog/cover-images/photo.jpg');
});
