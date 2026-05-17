<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFaqCategories();
        $this->seedFaqs();
        $this->seedBlogCategories();
        $this->seedBlogTags();
        $this->seedTicketCategories();
    }

    private function seedFaqCategories(): void
    {
        foreach ([
            ['name' => 'Getting started', 'slug' => 'getting-started', 'sort_order' => 1],
            ['name' => 'Schedules & claims', 'slug' => 'schedules-claims', 'sort_order' => 2],
            ['name' => 'Account & safety', 'slug' => 'account-safety', 'sort_order' => 3],
        ] as $category) {
            FaqCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }

    private function seedFaqs(): void
    {
        $categories = FaqCategory::pluck('id', 'slug');

        $faqs = [
            ['getting-started', 'What is SpeakLoud?', 'SpeakLoud connects language learners with partners for scheduled or direct practice sessions.'],
            ['getting-started', 'Is SpeakLoud free?', 'Yes. Core features are free while we grow the community.'],
            ['schedules-claims', 'How do I publish a time slot?', 'Open Schedule, create a recurring or one-off slot, and set your practice language.'],
            ['schedules-claims', 'What happens when I accept a claim?', 'A private conversation opens so you can coordinate before your session.'],
            ['account-safety', 'How do I block someone?', 'Visit their profile and use the block option. Blocked users never appear in search.'],
            ['account-safety', 'How do I report a user?', 'Use the report button on their profile or in chat. Our team reviews reports promptly.'],
        ];

        foreach ($faqs as $index => [$categorySlug, $question, $answer]) {
            Faq::firstOrCreate(
                ['question' => $question],
                [
                    'category_id' => $categories[$categorySlug] ?? null,
                    'answer'      => $answer,
                    'is_active'   => true,
                    'sort_order'  => $index + 1,
                ]
            );
        }
    }

    private function seedBlogCategories(): void
    {
        foreach ([
            ['name' => 'Tips & tricks', 'slug' => 'tips'],
            ['name' => 'Community', 'slug' => 'community'],
            ['name' => 'Product updates', 'slug' => 'updates'],
        ] as $category) {
            BlogCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }

    private function seedBlogTags(): void
    {
        foreach (['beginners', 'pronunciation', 'culture', 'motivation', 'grammar'] as $tag) {
            BlogTag::firstOrCreate(['slug' => $tag], ['name' => ucfirst($tag)]);
        }
    }

    private function seedTicketCategories(): void
    {
        foreach ([
            ['name' => 'Account', 'slug' => 'account'],
            ['name' => 'Billing', 'slug' => 'billing'],
            ['name' => 'Bug report', 'slug' => 'bug'],
            ['name' => 'Feature request', 'slug' => 'feature'],
        ] as $category) {
            TicketCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }

    public function seedBlogPosts(User $author): void
    {
        if (BlogPost::exists()) {
            return;
        }

        $categoryId = BlogCategory::where('slug', 'tips')->value('id');
        $tagIds = BlogTag::pluck('id')->take(3);

        $posts = [
            [
                'title'   => 'Five ways to find the right practice partner',
                'excerpt' => 'Filters, bios, and tags help you match with someone at your level.',
                'body'    => '<p>Start with language and level filters, then read bios for shared interests. Send a short, friendly claim note.</p>',
            ],
            [
                'title'   => 'Making the most of your first session',
                'excerpt' => 'A simple structure keeps first conversations comfortable.',
                'body'    => '<p>Warm up in your shared language, agree on a topic, and leave five minutes for feedback at the end.</p>',
            ],
            [
                'title'   => 'Why recurring slots work better than one-offs',
                'excerpt' => 'Consistency beats intensity for long-term fluency.',
                'body'    => '<p>Weekly slots build habit and trust with the same partners over time.</p>',
            ],
        ];

        foreach ($posts as $index => $post) {
            $slug = Str::slug($post['title']);

            $blogPost = BlogPost::create([
                'author_id'    => $author->id,
                'category_id'  => $categoryId,
                'title'        => $post['title'],
                'slug'         => $slug,
                'excerpt'      => $post['excerpt'],
                'body'         => $post['body'],
                'status'       => 'published',
                'published_at' => now()->subDays(10 - $index),
            ]);

            $blogPost->tags()->sync($tagIds->random(min(2, $tagIds->count()))->all());
        }
    }
}
