<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\SitemapBuilder;
use Illuminate\Support\Facades\Cache;

test('home page includes seo meta tags and blog cover image alt', function () {
    $category = BlogCategory::firstOrCreate(
        ['slug' => 'tips'],
        ['name' => 'Tips & tricks']
    );

    $author = User::factory()->create();

    UserProfile::create([
        'user_id' => $author->id,
        'username' => 'seoauthor',
        'display_name' => 'SEO Author',
        'profile_slug' => 'seoauthor',
    ]);

    BlogPost::create([
        'author_id' => $author->id,
        'category_id' => $category->id,
        'title' => 'SEO spotlight post',
        'slug' => 'seo-spotlight-post',
        'excerpt' => 'Meta description source text for testing.',
        'body' => '<p>Body</p>',
        'cover_image_path' => 'images/blog/partners.svg',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="description"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('property="og:locale"', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"Organization"', false)
        ->assertSee('images/og-default.png', false)
        ->assertSee('Practice languages with real people | SpeakLoud', false)
        ->assertSee('alt="Cover image for SEO spotlight post"', false)
        ->assertSee('width="1200"', false)
        ->assertSee('height="675"', false);
});

test('blog post page includes article meta and cover image', function () {
    $author = User::factory()->create();

    UserProfile::create([
        'user_id' => $author->id,
        'username' => 'postauthor',
        'display_name' => 'Post Author',
        'profile_slug' => 'postauthor',
    ]);

    $post = BlogPost::create([
        'author_id' => $author->id,
        'title' => 'Article for SEO',
        'slug' => 'article-for-seo',
        'excerpt' => 'Short excerpt used in meta description.',
        'body' => '<p>Content</p>',
        'cover_image_path' => 'images/blog/welcome.svg',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertSee('Article for SEO | SpeakLoud', false)
        ->assertSee('property="og:type" content="article"', false)
        ->assertSee('property="article:published_time"', false)
        ->assertSee('property="article:author" content="Post Author"', false)
        ->assertSee('Short excerpt used in meta description.', false)
        ->assertSee('alt="Cover image for Article for SEO"', false)
        ->assertSee('images/blog/welcome.svg', false)
        ->assertSee('"@type":"BlogPosting"', false)
        ->assertSee('"headline":"Article for SEO"', false);
});

test('about page has unique title and description', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('About SpeakLoud | SpeakLoud', false)
        ->assertSee('Learn how SpeakLoud helps language learners', false);
});

test('login page is noindex', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', false);
});

test('faq page includes faqpage json-ld', function () {
    Faq::create([
        'question' => 'How do claims work?',
        'answer' => 'Send a claim to join a schedule.',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->get(route('faq.index'))
        ->assertOk()
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('How do claims work?', false)
        ->assertSee('Send a claim to join a schedule.', false);
});

test('public profile page has unique seo meta', function () {
    $user = User::factory()->create();

    UserProfile::create([
        'user_id' => $user->id,
        'username' => 'seoprofile',
        'profile_slug' => 'seoprofile',
        'display_name' => 'SEO Profile User',
        'bio' => 'I love practicing Spanish every morning.',
        'is_private' => false,
    ]);

    $this->get(route('users.show', 'seoprofile'))
        ->assertOk()
        ->assertSee('SEO Profile User | SpeakLoud', false)
        ->assertSee('I love practicing Spanish every morning.', false)
        ->assertSee('"@type":"Person"', false)
        ->assertDontSee('name="robots" content="noindex, nofollow"', false);
});

test('private profile page is noindex and does not leak bio', function () {
    $user = User::factory()->create();

    UserProfile::create([
        'user_id' => $user->id,
        'username' => 'privateuser',
        'profile_slug' => 'privateuser',
        'display_name' => 'Private User',
        'bio' => 'Hidden bio secret content.',
        'is_private' => true,
    ]);

    $this->get(route('users.show', 'privateuser'))
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', false)
        ->assertSee('This SpeakLoud profile is private.', false)
        ->assertDontSee('Hidden bio secret content.', false)
        ->assertDontSee('"@type":"Person"', false);
});

test('json-ld escapes script breaking tags from user content', function () {
    $author = User::factory()->create();

    UserProfile::create([
        'user_id' => $author->id,
        'username' => 'xssauthor',
        'display_name' => 'XSS Author',
        'profile_slug' => 'xssauthor',
    ]);

    $post = BlogPost::create([
        'author_id' => $author->id,
        'title' => 'Break </script><script>alert(1)</script> out',
        'slug' => 'xss-json-ld-post',
        'excerpt' => 'Safe excerpt.',
        'body' => '<p>Content</p>',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $html = $this->get(route('blog.show', $post->slug))->assertOk()->getContent();

    expect($html)
        ->not->toContain('</script><script>alert(1)</script>')
        ->and($html)->toContain('\\u003C\\/script\\u003E');
});

test('sitemap excludes inactive users', function () {
    Cache::forget(SitemapBuilder::CACHE_KEY);

    $inactive = User::factory()->create(['status' => 'banned']);

    UserProfile::create([
        'user_id' => $inactive->id,
        'username' => 'inactiveuser',
        'display_name' => 'Inactive User',
        'profile_slug' => 'inactiveuser',
        'is_private' => false,
    ]);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertDontSee(route('users.show', 'inactiveuser'), false);
});

test('authenticated tickets page is noindex', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', false);
});

test('sitemap includes public urls and excludes drafts and private profiles', function () {
    Cache::forget(SitemapBuilder::CACHE_KEY);

    $author = User::factory()->create();

    UserProfile::create([
        'user_id' => $author->id,
        'username' => 'sitemapauthor',
        'display_name' => 'Sitemap Author',
        'profile_slug' => 'sitemapauthor',
        'is_private' => false,
    ]);

    $privateUser = User::factory()->create();

    UserProfile::create([
        'user_id' => $privateUser->id,
        'username' => 'sitemapprivate',
        'display_name' => 'Private Sitemap',
        'profile_slug' => 'sitemapprivate',
        'is_private' => true,
    ]);

    BlogPost::create([
        'author_id' => $author->id,
        'title' => 'Published sitemap post',
        'slug' => 'published-sitemap-post',
        'excerpt' => 'Published.',
        'body' => '<p>Body</p>',
        'status' => 'published',
        'published_at' => now(),
    ]);

    BlogPost::create([
        'author_id' => $author->id,
        'title' => 'Draft sitemap post',
        'slug' => 'draft-sitemap-post',
        'excerpt' => 'Draft.',
        'body' => '<p>Body</p>',
        'status' => 'draft',
        'published_at' => null,
    ]);

    $response = $this->get(route('sitemap'))
        ->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/xml');

    $response
        ->assertSee(route('home'), false)
        ->assertSee(route('blog.show', 'published-sitemap-post'), false)
        ->assertSee(route('users.show', 'sitemapauthor'), false)
        ->assertDontSee(route('blog.show', 'draft-sitemap-post'), false)
        ->assertDontSee(route('users.show', 'sitemapprivate'), false);
});

test('robots txt disallows private paths and links sitemap', function () {
    $this->get(route('robots'))
        ->assertOk()
        ->assertSee('Disallow: /login', false)
        ->assertSee('Disallow: /schedule', false)
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
});
