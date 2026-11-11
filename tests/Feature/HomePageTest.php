<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use App\Models\UserProfile;

test('home page shows recent published blog posts', function () {
    $category = BlogCategory::firstOrCreate(
        ['slug' => 'tips'],
        ['name' => 'Tips & tricks']
    );

    $author = User::factory()->create();

    UserProfile::create([
        'user_id' => $author->id,
        'username' => 'blogauthor',
        'display_name' => 'Blog Author',
        'profile_slug' => 'blogauthor',
    ]);

    BlogPost::create([
        'author_id' => $author->id,
        'category_id' => $category->id,
        'title' => 'Home page blog spotlight',
        'slug' => 'home-page-blog-spotlight',
        'excerpt' => 'This post should appear on the landing page.',
        'body' => '<p>Full article body.</p>',
        'cover_image_path' => 'images/blog/partners.svg',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('From the blog')
        ->assertSee('Home page blog spotlight')
        ->assertSee('This post should appear on the landing page.')
        ->assertSee(route('blog.index'), false)
        ->assertSee('alt="Cover image for Home page blog spotlight"', false)
        ->assertSee('aspect-[2/1] md:aspect-[16/9]', false)
        ->assertSee('text-[2rem] sm:text-[2.75rem] md:text-[3.25rem]', false);
});

test('home page footer links are organized and present', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('aria-label="Footer"', false)
        ->assertSee(route('about'), false)
        ->assertSee(route('blog.index'), false)
        ->assertSee(route('faq.index'), false)
        ->assertSee(route('contact'), false)
        ->assertSee(route('donate'), false)
        ->assertSee(route('support'), false)
        ->assertSee(route('terms'), false)
        ->assertSee('mohammaddv', false);
});

test('home page includes mobile bottom quick access destinations', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('aria-label="Quick access"', false)
        ->assertSee(route('home'), false)
        ->assertSee(route('discover'), false)
        ->assertSee(route('schedule'), false)
        ->assertSee(route('messages'), false)
        ->assertSee(route('profile'), false);
});

test('home page exposes desktop nav donate link and mobile hero donate button', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('donate'), false)
        ->assertSee('Donate', false)
        ->assertSee('M21 8.25c0-2.485-2.099-4.5-4.688-4.5', false)
        ->assertSee('md:hidden inline-flex items-center justify-center text-[14px] font-semibold text-[#FF8C42]', false);
});

test('home page sets viewer timezone and exposes mobile menu controls with livewire for spa nav', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('viewer_timezone=', false)
        ->assertSee('aria-label="Open menu"', false)
        ->assertSee('data-mobile-nav-toggle', false)
        ->assertSee('data-mobile-nav-drawer', false)
        ->assertSee('livewire.js', false)
        ->assertSee('wire:navigate', false);
});

test('home page forces light color scheme for ios dark mode safety', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('color-scheme: light only', false)
        ->assertSee('name="color-scheme" content="light only"', false)
        ->assertSee('name="supported-color-schemes" content="light"', false);
});

test('authenticated home page shows claim a slot instead of start free', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Claim a slot', false)
        ->assertSee(route('discover'), false)
        ->assertDontSee('Start free', false);
});

test('home page shows how-to steps and video section above blog', function () {
    $category = BlogCategory::firstOrCreate(
        ['slug' => 'tips'],
        ['name' => 'Tips & tricks']
    );

    $author = User::factory()->create();

    UserProfile::create([
        'user_id' => $author->id,
        'username' => 'howtoauthor',
        'display_name' => 'HowTo Author',
        'profile_slug' => 'howtoauthor',
    ]);

    BlogPost::create([
        'author_id' => $author->id,
        'category_id' => $category->id,
        'title' => 'How-to blog marker',
        'slug' => 'how-to-blog-marker',
        'excerpt' => 'Blog should appear after the video section.',
        'body' => '<p>Body</p>',
        'cover_image_path' => 'images/blog/partners.svg',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    $html = $this->get(route('home'))
        ->assertOk()
        ->assertSee('How to use', false)
        ->assertSee('Get from signup to your first practice session.', false)
        ->assertSee('Create your profile', false)
        ->assertSee('Publish practice hours', false)
        ->assertSee('Claim or accept partners', false)
        ->assertSee('Talk and message', false)
        ->assertSee('images/screenshots/create-your-profile.webp', false)
        ->assertSee('images/screenshots/publish-practice-hours.webp', false)
        ->assertSee('images/screenshots/claim-or-accept-partners.webp', false)
        ->assertSee('images/screenshots/talk-and-message.webp', false)
        ->assertSee('"@type":"HowTo"', false)
        ->assertSee('"name":"Create your profile"', false)
        ->assertSee('See it in 60 seconds.', false)
        ->assertSee('From the blog', false)
        ->assertSee('How-to blog marker', false)
        ->assertDontSee('Watch how a session goes.', false)
        ->getContent();

    $howToPos = strpos($html, 'Get from signup to your first practice session.');
    $videoPos = strpos($html, 'See it in 60 seconds.');
    $blogPos = strpos($html, 'From the blog');

    expect($howToPos)->not->toBeFalse()
        ->and($videoPos)->not->toBeFalse()
        ->and($blogPos)->not->toBeFalse()
        ->and($howToPos)->toBeLessThan($videoPos)
        ->and($videoPos)->toBeLessThan($blogPos);
});

test('home page no longer shows the old short how it works strip copy alone', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Mark when you are free, weekly or one-off.', false)
        ->assertDontSee('Other learners apply to your slot with a note.', false);
});
