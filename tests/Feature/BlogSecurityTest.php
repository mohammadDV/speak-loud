<?php

use App\Models\BlogPost;
use App\Models\User;
use Livewire\Volt\Volt;

test('blog post body is sanitized on save', function () {
    $author = User::factory()->create();

    $post = BlogPost::create([
        'author_id' => $author->id,
        'title' => 'Safe post',
        'slug' => 'safe-post',
        'excerpt' => 'Excerpt',
        'body' => '<p>Hello</p><script>alert("xss")</script><img src=x onerror=alert(1)>',
        'status' => 'published',
        'published_at' => now(),
    ]);

    expect($post->fresh()->body)
        ->toContain('<p>Hello</p>')
        ->not->toContain('<script>')
        ->not->toContain('onerror')
        ->not->toContain('<img');
});

test('blog show does not render script tags from body', function () {
    $author = User::factory()->create();

    BlogPost::create([
        'author_id' => $author->id,
        'title' => 'XSS attempt',
        'slug' => 'xss-attempt',
        'excerpt' => 'Excerpt',
        'body' => '<p>Visible</p><script>alert(1)</script>',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Volt::test('blog.show', ['slug' => 'xss-attempt'])
        ->assertSee('Visible')
        ->assertDontSee('<script>', false)
        ->assertDontSee('alert(1)');
});
