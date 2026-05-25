<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use App\Models\UserProfile;

test('home page includes seo meta tags and blog cover image alt', function () {
    $category = BlogCategory::firstOrCreate(
        ['slug' => 'tips'],
        ['name' => 'Tips & tricks']
    );

    $author = User::factory()->create();

    UserProfile::create([
        'user_id'      => $author->id,
        'username'     => 'seoauthor',
        'display_name' => 'SEO Author',
        'profile_slug' => 'seoauthor',
    ]);

    BlogPost::create([
        'author_id'        => $author->id,
        'category_id'      => $category->id,
        'title'            => 'SEO spotlight post',
        'slug'             => 'seo-spotlight-post',
        'excerpt'          => 'Meta description source text for testing.',
        'body'             => '<p>Body</p>',
        'cover_image_path' => 'images/blog/partners.svg',
        'status'           => 'published',
        'published_at'     => now(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="description"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('Practice languages with real people | SpeakLoud', false)
        ->assertSee('alt="Cover image for SEO spotlight post"', false);
});

test('blog post page includes article meta and cover image', function () {
    $author = User::factory()->create();

    UserProfile::create([
        'user_id'      => $author->id,
        'username'     => 'postauthor',
        'display_name' => 'Post Author',
        'profile_slug' => 'postauthor',
    ]);

    $post = BlogPost::create([
        'author_id'        => $author->id,
        'title'            => 'Article for SEO',
        'slug'             => 'article-for-seo',
        'excerpt'          => 'Short excerpt used in meta description.',
        'body'             => '<p>Content</p>',
        'cover_image_path' => 'images/blog/welcome.svg',
        'status'           => 'published',
        'published_at'     => now(),
    ]);

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertSee('Article for SEO | SpeakLoud', false)
        ->assertSee('property="og:type" content="article"', false)
        ->assertSee('Short excerpt used in meta description.', false)
        ->assertSee('alt="Cover image for Article for SEO"', false)
        ->assertSee('images/blog/welcome.svg', false);
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
