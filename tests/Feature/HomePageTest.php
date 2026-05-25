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
        'user_id'      => $author->id,
        'username'     => 'blogauthor',
        'display_name' => 'Blog Author',
        'profile_slug' => 'blogauthor',
    ]);

    BlogPost::create([
        'author_id'        => $author->id,
        'category_id'      => $category->id,
        'title'            => 'Home page blog spotlight',
        'slug'             => 'home-page-blog-spotlight',
        'excerpt'          => 'This post should appear on the landing page.',
        'body'             => '<p>Full article body.</p>',
        'cover_image_path' => 'images/blog/partners.svg',
        'status'           => 'published',
        'published_at'     => now()->subDay(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('From the blog')
        ->assertSee('Home page blog spotlight')
        ->assertSee('This post should appear on the landing page.')
        ->assertSee(route('blog.index'), false)
        ->assertSee('alt="Cover image for Home page blog spotlight"', false);
});
