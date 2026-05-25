<?php

use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\User;
use App\Models\UserProfile;
use Livewire\Volt\Volt;

test('blog and faq are separate pages', function () {
    Faq::create([
        'question'   => 'What is SpeakLoud?',
        'answer'     => 'A community for language practice.',
        'is_active'  => true,
        'sort_order' => 1,
    ]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('<h1 class="text-2xl font-bold text-[#3D2B1F]">Blog</h1>', false)
        ->assertDontSee('Frequently asked questions', false);

    $this->get(route('faq.index'))
        ->assertOk()
        ->assertSee('Frequently asked questions', false)
        ->assertSee('What is SpeakLoud?', false)
        ->assertDontSee('<h1 class="text-2xl font-bold text-[#3D2B1F]">Blog</h1>', false);
});

test('blog index paginates six posts per page', function () {
    $author = User::factory()->create();

    UserProfile::create([
        'user_id'      => $author->id,
        'username'     => 'paginateauthor',
        'display_name' => 'Paginate Author',
        'profile_slug' => 'paginateauthor',
    ]);

    foreach (range(1, 8) as $n) {
        BlogPost::create([
            'author_id'        => $author->id,
            'title'            => "Pagination post {$n}",
            'slug'             => "pagination-post-{$n}",
            'excerpt'          => "Excerpt for post {$n}.",
            'body'             => '<p>Body</p>',
            'cover_image_path' => 'images/blog/partners.svg',
            'status'           => 'published',
            'published_at'     => now()->subDays($n),
        ]);
    }

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Pagination post 1')
        ->assertSee('Pagination post 6')
        ->assertDontSee('Pagination post 7');

    $this->get(route('blog.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Pagination post 7')
        ->assertSee('Pagination post 8')
        ->assertDontSee('Pagination post 6');
});

test('blog index page two via livewire pagination', function () {
    $author = User::factory()->create();

    UserProfile::create([
        'user_id'      => $author->id,
        'username'     => 'livewireblog',
        'display_name' => 'Livewire Blog',
        'profile_slug' => 'livewireblog',
    ]);

    foreach (range(1, 7) as $n) {
        BlogPost::create([
            'author_id'        => $author->id,
            'title'            => "Livewire page post {$n}",
            'slug'             => "livewire-page-post-{$n}",
            'excerpt'          => 'Excerpt',
            'body'             => '<p>Body</p>',
            'cover_image_path' => 'images/blog/welcome.svg',
            'status'           => 'published',
            'published_at'     => now()->subHours($n),
        ]);
    }

    Volt::test('blog.index')
        ->call('gotoPage', 2)
        ->assertSee('Livewire page post 7')
        ->assertDontSee('Livewire page post 1');
});
