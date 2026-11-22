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

test('blog show renders safe internal links from body', function () {
    $author = User::factory()->create();

    BlogPost::create([
        'author_id' => $author->id,
        'title' => 'Linked post',
        'slug' => 'linked-post',
        'excerpt' => 'Excerpt',
        'body' => '<p>Practice with <a href="/learn">Learn</a> between conversations.</p>',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Volt::test('blog.show', ['slug' => 'linked-post'])
        ->assertSee('Practice with')
        ->assertSee('Learn')
        ->assertSee('href="/learn"', false)
        ->assertSee('rel="noopener noreferrer"', false);
});

test('blog show keeps headings and applies prose styles for rich html body', function () {
    $author = User::factory()->create();

    BlogPost::create([
        'author_id' => $author->id,
        'title' => 'Styled post',
        'slug' => 'styled-post',
        'excerpt' => 'Excerpt',
        'body' => '<h2>Why consistency matters</h2><p>Open <a href="/learn">Learn</a> daily.</p><h3>Small steps</h3><p>Ten minutes is enough.</p>',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->get(route('blog.show', 'styled-post'))
        ->assertOk()
        ->assertSee('<h2>Why consistency matters</h2>', false)
        ->assertSee('<h3>Small steps</h3>', false)
        ->assertSee('href="/learn"', false)
        ->assertSee('blog-content', false);
});
